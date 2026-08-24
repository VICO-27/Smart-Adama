<?php

namespace App\Services\Chat;

use App\Models\ChatMessage;
use App\Models\ChatMessageSource;
use App\Models\ChatSession;
use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Services\MarkdownService;
use App\Services\RAG\GroundingDecisionService;
use App\Services\RAG\PromptBuilderService;
use App\Services\RAG\QueryUnderstandingService;
use App\Services\RAG\RetrievalService;
use App\Services\RAG\FollowUpResolutionService;
use App\Services\RAG\ChapterSummaryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatOrchestrator
{
    public function __construct(
        private readonly RetrievalService          $retriever,
        private readonly PromptBuilderService      $promptBuilder,
        private readonly LLMGatewayInterface       $llm,
        private readonly MarkdownService           $markdown,
        private readonly QueryUnderstandingService $queryUnderstanding,
        private readonly GroundingDecisionService  $groundingDecision,
        private readonly FollowUpResolutionService $followUpResolution,
        private readonly ChapterSummaryService     $chapterSummary,
    ) {
    }

    /**
     * Process a user message within a chat session and generate an AI response.
     */
    public function handleMessage(ChatSession $session, string $query): array
    {
        // For non-streaming fallback
        $understanding = $this->queryUnderstanding->analyze($query);
        $responseMode = $understanding['response_mode'] ?? 'NORMAL';
        $isFollowUp = $understanding['is_follow_up'] ?? false;
        
        if ($responseMode === 'CHAPTER_SUMMARY') {
            $chapterNumber = $understanding['chapter_number'] ?? 0;
            $chunks = $this->retriever->getChapterChunks($chapterNumber);
            $chunks = $this->chapterSummary->summarize($chunks);
            $grounding = ['isGrounded' => true, 'selectedSources' => $chunks];
        } elseif ($responseMode === 'CHAPTER_SUMMARY_UNKNOWN') {
            $chunks = collect([]);
            $grounding = ['isGrounded' => false, 'selectedSources' => $chunks];
        } else {
            $searchQuery = $understanding['normalized_query'];
            
            if ($isFollowUp) {
                $lastUserMsg = $session->messages()->where('role', 'user')->orderByDesc('created_at')->first();
                $lastAssistantMsg = $session->messages()->where('role', 'assistant')->orderByDesc('created_at')->first();
                $resolvedQuery = $this->followUpResolution->resolve($query, $lastUserMsg?->content, $lastAssistantMsg?->content);
                if ($resolvedQuery) $searchQuery = $resolvedQuery;
            }
            
            $candidates = $this->retriever->search($understanding['original_query'], $searchQuery, 20);
            
            if ($isFollowUp && isset($lastAssistantMsg)) {
                $sourceIds = $lastAssistantMsg->sources()->pluck('content_chunk_id')->toArray();
                if (!empty($sourceIds)) {
                    $previousChunks = $this->retriever->getChunksByIds($sourceIds);
                    $candidates = $candidates->merge($previousChunks)->unique('id')->values();
                }
            }
            
            $grounding = $this->groundingDecision->evaluate($candidates, $understanding, $responseMode);
            $chunks = $grounding['selectedSources'];
        }

        $history = $session->messages()->orderBy('created_at', 'asc')->get()
            ->map(fn ($msg) => ['role' => $msg->role, 'content' => $msg->content])->toArray();

        $messages = $this->promptBuilder->buildMessages($history, $chunks, $query, $grounding, $understanding['is_conversational'], $responseMode);
        
        $aiResponseText = $this->llm->chat($messages);
        $aiResponseHtml = $this->markdown->toHtml($aiResponseText);

        $assistantMessage = DB::transaction(function () use ($session, $query, $aiResponseHtml, $chunks, $grounding) {
            $session->messages()->create(['role' => 'user', 'content' => $query]);
            $assistantMsg = $session->messages()->create([
                'role' => 'assistant', 'content' => $aiResponseHtml,
                'metadata' => ['grounded' => $grounding['isGrounded'], 'chunk_count' => count($chunks), 'is_markdown' => true],
            ]);
            if ($grounding['isGrounded'] && !empty($chunks)) {
                foreach ($chunks as $chunk) {
                    ChatMessageSource::create(['chat_message_id' => $assistantMsg->id, 'content_chunk_id' => $chunk['id'], 'similarity_score' => $chunk['rrf_score'] ?? 0]);
                }
            }
            return $assistantMsg;
        });

        return ['message' => $assistantMessage, 'chunks' => $chunks, 'grounded' => $grounding['isGrounded']];
    }

    /**
     * Process a streaming chat message, executing callbacks for SSE events.
     */
    public function handleStreamMessage(
        ChatSession $session,
        string $query,
        ChatMessage $userMessage,
        ChatMessage $assistantMessage,
        callable $emitActivity,
        callable $emitToken,
        callable $emitError,
        callable $emitComplete
    ): void {
        $responseContent = '';
        $chunks = collect([]);
        $citations = [];
        $grounding = ['isGrounded' => false];

        try {
            $emitActivity('understanding', 'Understanding your question...');
            
            $understanding = $this->queryUnderstanding->analyze($query);
            $isConversational = $understanding['is_conversational'];
            $responseMode = $understanding['response_mode'] ?? 'NORMAL';
            $isFollowUp = $understanding['is_follow_up'] ?? false;

            if ($responseMode === 'CHAPTER_SUMMARY_UNKNOWN') {
                $responseContent = "Could you please specify which chapter you would like me to summarize? (e.g., 'Summarize Chapter 1')";
                $emitToken($responseContent);
            } elseif ($responseMode === 'CHAPTER_SUMMARY') {
                $emitActivity('searching', 'Retrieving chapter contents...');
                $chapterNumber = $understanding['chapter_number'] ?? 0;
                
                $chunks = $this->retriever->getChapterChunks($chapterNumber);
                
                if ($chunks->isEmpty()) {
                    $responseContent = "I couldn't find any content for Chapter {$chapterNumber}.";
                    $emitToken($responseContent);
                } else {
                    if ($chunks->count() > 12) {
                        $emitActivity('preparing', 'Compressing large chapter (this may take a moment)...');
                    }
                    
                    $chunks = $this->chapterSummary->summarize($chunks);
                    
                    $grounding['isGrounded'] = true;
                    $grounding['selectedSources'] = $chunks;
                    
                    $history = $session->messages()->whereNot('id', $userMessage->id)->orderBy('created_at')
                        ->get()->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->toArray();
                        
                    $emitActivity('generating', 'Generating chapter summary...');
                    $messages = $this->promptBuilder->buildMessages($history, $chunks, $query, $grounding, false, $responseMode);
                    
                    foreach ($this->llm->streamChat($messages) as $token) {
                        if (connection_aborted()) break;
                        $responseContent .= $token;
                        $emitToken($token);
                    }
                }
            } else {
                if ($isConversational) {
                    $grounding['isGrounded'] = true;
                } else {
                    $searchQuery = $understanding['normalized_query'];
                    $unresolved = false;
                    
                    if ($isFollowUp) {
                        $lastUserMsg = $session->messages()->where('role', 'user')->whereNot('id', $userMessage->id)->orderByDesc('created_at')->first();
                        $lastAssistantMsg = $session->messages()->where('role', 'assistant')->orderByDesc('created_at')->first();
                        
                        $emitActivity('understanding', 'Resolving conversation context...');
                        $resolvedQuery = $this->followUpResolution->resolve($query, $lastUserMsg?->content, $lastAssistantMsg?->content);
                        
                        if ($resolvedQuery) {
                            $searchQuery = $resolvedQuery;
                        } else {
                            $unresolved = true;
                        }
                    }

                    if ($unresolved) {
                        $responseContent = "I'm not completely sure what you're referring to. Could you please clarify your question?";
                        $emitToken($responseContent);
                    } else {
                        $emitActivity('searching', 'Searching the Smart Adama knowledge base...');
                        $candidates = $this->retriever->search($understanding['original_query'], $searchQuery, 20); // Static pool, filtered later
                        
                        if ($isFollowUp && isset($lastAssistantMsg)) {
                            $sourceIds = $lastAssistantMsg->sources()->pluck('content_chunk_id')->toArray();
                            if (!empty($sourceIds)) {
                                $previousChunks = $this->retriever->getChunksByIds($sourceIds);
                                $candidates = $candidates->merge($previousChunks)->unique('id')->values();
                            }
                        }
                        
                        $emitActivity('reviewing', 'Reviewing relevant information...');
                        $grounding = $this->groundingDecision->evaluate($candidates, $understanding, $responseMode);
                        $chunks = $grounding['selectedSources'];
                    }
                }

                if (!isset($unresolved) || !$unresolved) {
                    $history = $session->messages()->whereNot('id', $userMessage->id)->orderBy('created_at')
                        ->get()->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->toArray();

                    $emitActivity('preparing', 'Preparing your answer...');
                    $messages = $this->promptBuilder->buildMessages($history, $chunks, $query, $grounding, $isConversational, $responseMode);

                    $emitActivity('generating', 'Generating your response...');
                    foreach ($this->llm->streamChat($messages) as $token) {
                        if (connection_aborted()) break;
                        $responseContent .= $token;
                        $emitToken($token);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('ChatOrchestrator: LLM stream error', ['session_id' => $session->id, 'error' => $e->getMessage()]);
            $emitError('AI_PROVIDER_UNAVAILABLE', 'The AI service is temporarily unavailable: ' . $e->getMessage());
            return;
        }

        // Finalize
        if (!empty(trim($responseContent))) {
            DB::transaction(function () use ($assistantMessage, $responseContent, $chunks, &$citations) {
                $assistantMessage->update(['content' => $responseContent]);
                foreach ($chunks as $chunk) {
                    ChatMessageSource::create(['chat_message_id' => $assistantMessage->id, 'content_chunk_id' => $chunk['id'], 'similarity_score' => $chunk['rrf_score'] ?? 0]);
                }
                $citations = collect($chunks)->map(fn ($chunk) => [
                    'chunk_id' => $chunk['id'], 'page_number' => $chunk['page_number'] ?? 'Unknown',
                    'heading' => $chunk['structural_context']['heading'] ?? 'General Context',
                    'excerpt' => mb_substr($chunk['chunk_text'], 0, 200), 'rrf_score' => $chunk['rrf_score'] ?? 0,
                ])->toArray();
            });
            $emitComplete($assistantMessage->id, !empty($citations), $citations, $responseContent);
        } else {
            $assistantMessage->delete();
        }
    }

}