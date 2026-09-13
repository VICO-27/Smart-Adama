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
    public function handleMessage(ChatSession $session, string $query, array $context = []): array
    {
        $startTime = microtime(true);
        $requestId = $context['request_id'] ?? (string) \Illuminate\Support\Str::uuid();
        Log::info("[AI_TIMING] request_id={$requestId} stage=request_start mode=non_streaming", ['session_id' => $session->id]);

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
                $lastUserMsg = $session->messages()->where('role', 'user')->where('content', '!=', '')->orderByDesc('created_at')->first();
                $lastAssistantMsg = $session->messages()->where('role', 'assistant')->where('content', '!=', '')->orderByDesc('created_at')->first();
                $resolvedQuery = $this->followUpResolution->resolve($query, $lastUserMsg?->content, $lastAssistantMsg?->content, $context);
                if ($resolvedQuery) {
                    $searchQuery = $resolvedQuery;
                    if ($searchQuery !== $understanding['normalized_query']) {
                        $resolvedUnderstanding = $this->queryUnderstanding->analyze($searchQuery);
                        if (!empty($resolvedUnderstanding['chapter_number'])) {
                            $understanding['chapter_number'] = $resolvedUnderstanding['chapter_number'];
                        }
                        $understanding['normalized_query'] = $searchQuery;
                    }
                }
            }

            $activeChapterId = null;
            if (!empty($understanding['chapter_number'])) {
                $matchedChapter = DB::table('chapters')
                    ->where('order', $understanding['chapter_number'])
                    ->orWhere('title', 'ILIKE', "Ch-{$understanding['chapter_number']}:%")
                    ->orWhere('title', 'ILIKE', "Chapter {$understanding['chapter_number']}:%")
                    ->first();
                if ($matchedChapter) {
                    $activeChapterId = $matchedChapter->id;
                }
            } elseif (!empty($context['chapter_id'])) {
                $activeChapterId = $context['chapter_id'];
            }

            $excerptText = $understanding['excerpt_text'] ?? null;
            $candidates = $this->retriever->search(
                $understanding['original_query'],
                $searchQuery,
                8,
                $activeChapterId,
                $excerptText
            );

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

        $history = $session->messages()->where('content', '!=', '')->orderBy('created_at', 'asc')->get()
            ->map(fn ($msg) => ['role' => $msg->role, 'content' => $msg->content])->toArray();

        $effectiveQuery = ($isFollowUp && !empty($searchQuery) && $searchQuery !== $query)
            ? $searchQuery
            : $query;

        $messages = $this->promptBuilder->buildMessages($history, $chunks, $effectiveQuery, $grounding, $understanding['is_conversational'], $responseMode, $context);

        $dispatchTime = microtime(true);
        Log::info("[AI_TIMING] request_id={$requestId} stage=provider_dispatch mode=non_streaming");
        $aiResponseText = $this->llm->chat($messages);
        $totalElapsed = round((microtime(true) - $startTime) * 1000);
        Log::info("[AI_TIMING] request_id={$requestId} stage=total_duration mode=non_streaming total_ms={$totalElapsed}");

        $cleanText = \App\Services\AI\ReasoningFilter::strip($aiResponseText);
        $aiResponseHtml = $this->markdown->toHtml($cleanText);

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
        array $context,
        callable $emitActivity,
        callable $emitToken,
        callable $emitError,
        callable $emitComplete
    ): void {
        $requestId = $context['request_id'] ?? $context['client_request_id'] ?? (string) \Illuminate\Support\Str::uuid();
        $reqStartTime = microtime(true);
        $stageStartTime = $reqStartTime;

        $logTiming = function (string $stage, array $extra = []) use ($requestId, $reqStartTime, &$stageStartTime) {
            $now = microtime(true);
            $stageElapsed = round(($now - $stageStartTime) * 1000);
            $totalElapsed = round(($now - $reqStartTime) * 1000);
            $stageStartTime = $now;

            Log::info("[AI_TIMING] request_id={$requestId} stage={$stage} stage_ms={$stageElapsed} total_ms={$totalElapsed}", $extra);
        };

        $logTiming('request_start', ['session_id' => $session->id]);

        $responseContent = '';
        $chunks = collect([]);
        $citations = [];
        $grounding = ['isGrounded' => false];
        $ttftRecorded = false;

        try {
            $emitActivity('understanding', 'Understanding your question...');

            $understanding = $this->queryUnderstanding->analyze($query);
            $isConversational = $understanding['is_conversational'];
            $responseMode = $understanding['response_mode'] ?? 'NORMAL';
            $isFollowUp = $understanding['is_follow_up'] ?? false;

            $logTiming('query_understanding', ['mode' => $responseMode, 'conversational' => $isConversational]);

            if ($responseMode === 'CHAPTER_SUMMARY_UNKNOWN') {
                $responseContent = "Could you please specify which chapter you would like me to summarize? (e.g., 'Summarize Chapter 1')";
                $emitToken($responseContent);
            } elseif ($responseMode === 'CHAPTER_QUIZ_UNKNOWN') {
                $activeChapterId = $context['chapter_id'] ?? null;
                $activeChapterNum = null;
                if ($activeChapterId) {
                    $ch = DB::table('chapters')->where('id', $activeChapterId)->first();
                    if ($ch) {
                        $activeChapterNum = $ch->order;
                    }
                }
                if ($activeChapterNum !== null) {
                    $responseMode = 'CHAPTER_QUIZ';
                    $understanding['chapter_number'] = $activeChapterNum;
                } else {
                    $responseContent = "I'd love to quiz you! Which chapter or topic would you like to test yourself on? (e.g., 'Quiz on Chapter 1', 'Quiz on Chapter 8: Smart Social Services', or 'Quiz on Smart Mobility')";
                    $emitToken($responseContent);
                }
            }

            if ($responseMode !== 'CHAPTER_SUMMARY_UNKNOWN' && !($responseMode === 'CHAPTER_QUIZ_UNKNOWN' && empty($activeChapterNum))) {
                // Reusable closure to safely strip <think> tags from the streaming tokens
                $streamAndEmit = function(array $messages) use (&$responseContent, $emitToken, $requestId, $reqStartTime, &$ttftRecorded) {
                    $inThink = false;
                    $thinkBuffer = '';

                    foreach ($this->llm->streamChat($messages) as $token) {
                        if (connection_aborted()) break;

                        $thinkBuffer .= $token;

                        while (true) {
                            if (!$inThink) {
                                $pos = strpos($thinkBuffer, '<think>');
                                if ($pos !== false) {
                                    $inThink = true;
                                    $before = substr($thinkBuffer, 0, $pos);
                                    if ($before !== '') {
                                        if (!$ttftRecorded) {
                                            $ttftRecorded = true;
                                            $ttftMs = round((microtime(true) - $reqStartTime) * 1000);
                                            Log::info("[AI_TIMING] request_id={$requestId} stage=first_token_received (TTFT) elapsed_ms={$ttftMs}");
                                        }
                                        $responseContent .= $before;
                                        $emitToken($before);
                                    }
                                    $thinkBuffer = substr($thinkBuffer, $pos + 7);
                                    continue;
                                } else {
                                    if (strlen($thinkBuffer) > 7) {
                                        $safeToEmit = substr($thinkBuffer, 0, -7);
                                        if (!$ttftRecorded) {
                                            $ttftRecorded = true;
                                            $ttftMs = round((microtime(true) - $reqStartTime) * 1000);
                                            Log::info("[AI_TIMING] request_id={$requestId} stage=first_token_received (TTFT) elapsed_ms={$ttftMs}");
                                        }
                                        $responseContent .= $safeToEmit;
                                        $emitToken($safeToEmit);
                                        $thinkBuffer = substr($thinkBuffer, -7);
                                    }
                                    break;
                                }
                            } else {
                                $pos = strpos($thinkBuffer, '</think>');
                                if ($pos !== false) {
                                    $inThink = false;
                                    $thinkBuffer = substr($thinkBuffer, $pos + 8);
                                    continue;
                                } else {
                                    if (strlen($thinkBuffer) > 8) {
                                        $thinkBuffer = substr($thinkBuffer, -8);
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    if (!$inThink && $thinkBuffer !== '') {
                        if (!$ttftRecorded) {
                            $ttftRecorded = true;
                            $ttftMs = round((microtime(true) - $reqStartTime) * 1000);
                            Log::info("[AI_TIMING] request_id={$requestId} stage=first_token_received (TTFT) elapsed_ms={$ttftMs}");
                        }
                        $responseContent .= $thinkBuffer;
                        $emitToken($thinkBuffer);
                    }
                };

                if ($responseMode === 'CHAPTER_SUMMARY' || $responseMode === 'CHAPTER_QUIZ') {
                    $isQuiz = ($responseMode === 'CHAPTER_QUIZ');
                    $activityText = $isQuiz ? 'Retrieving chapter content for quiz...' : 'Retrieving chapter contents...';
                    $emitActivity('searching', $activityText);

                    $chapterNumber = $understanding['chapter_number'] ?? 0;
                    if (empty($chapterNumber) && !empty($context['chapter_id'])) {
                        $ch = DB::table('chapters')->where('id', $context['chapter_id'])->first();
                        if ($ch) {
                            $chapterNumber = $ch->order;
                        }
                    }

                    $chunks = $this->retriever->getChapterChunks($chapterNumber);
                    $logTiming('retrieval_chapter', ['chunk_count' => $chunks->count()]);

                    if ($chunks->isEmpty()) {
                        $responseContent = "I couldn't find any content for Chapter {$chapterNumber}.";
                        $emitToken($responseContent);
                    } else {
                        if ($chunks->count() > 12) {
                            $emitActivity('preparing', $isQuiz ? 'Preparing chapter quiz material...' : 'Compressing large chapter (this may take a moment)...');
                        }

                        $chunks = $this->chapterSummary->summarize($chunks);
                        $logTiming('chapter_summary_compressed');

                        $grounding['isGrounded'] = true;
                        $grounding['selectedSources'] = $chunks;

                        $history = $session->messages()->whereNotIn('id', [$userMessage->id, $assistantMessage->id])->where('content', '!=', '')->orderBy('created_at')
                            ->get()->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->toArray();

                        $emitActivity('generating', $isQuiz ? 'Generating your chapter quiz...' : 'Generating chapter summary...');
                        $messages = $this->promptBuilder->buildMessages($history, $chunks, $query, $grounding, false, $responseMode);
                        $logTiming('prompt_assembly');

                        $logTiming('provider_dispatch');
                        $streamAndEmit($messages);
                        $logTiming('stream_completion', ['chars' => strlen($responseContent)]);
                    }
                } else {
                    if ($isConversational) {
                        $grounding['isGrounded'] = true;
                    } else {
                        $searchQuery = $understanding['normalized_query'];
                        $unresolved = false;

                        if ($isFollowUp) {
                            $lastUserMsg = $session->messages()->where('role', 'user')->whereNot('id', $userMessage->id)->where('content', '!=', '')->orderByDesc('created_at')->first();
                            $lastAssistantMsg = $session->messages()->where('role', 'assistant')->whereNot('id', $assistantMessage->id)->where('content', '!=', '')->orderByDesc('created_at')->first();

                            $emitActivity('understanding', 'Resolving conversation context...');
                            $resolvedQuery = $this->followUpResolution->resolve($query, $lastUserMsg?->content, $lastAssistantMsg?->content, $context);

                            if ($resolvedQuery) {
                                $searchQuery = $resolvedQuery;
                                if ($searchQuery !== $understanding['normalized_query']) {
                                    $resolvedUnderstanding = $this->queryUnderstanding->analyze($searchQuery);
                                    if (!empty($resolvedUnderstanding['chapter_number'])) {
                                        $understanding['chapter_number'] = $resolvedUnderstanding['chapter_number'];
                                    }
                                    $understanding['normalized_query'] = $searchQuery;
                                }
                            } elseif ($this->isPurelyAnaphoric($query)) {
                                $unresolved = true;
                            } else {
                                $searchQuery = $understanding['normalized_query'];
                            }
                            $logTiming('follow_up_resolution', ['resolved' => !$unresolved, 'search_query' => $searchQuery]);
                        }

                        if ($unresolved) {
                            $responseContent = "I'm not completely sure what you're referring to. Could you please clarify your question?";
                            $emitToken($responseContent);
                        } else {
                            $activeChapterId = null;
                            if (!empty($understanding['chapter_number'])) {
                                $matchedChapter = DB::table('chapters')
                                    ->where('order', $understanding['chapter_number'])
                                    ->orWhere('title', 'ILIKE', "Ch-{$understanding['chapter_number']}:%")
                                    ->orWhere('title', 'ILIKE', "Chapter {$understanding['chapter_number']}:%")
                                    ->first();
                                if ($matchedChapter) {
                                    $activeChapterId = $matchedChapter->id;
                                }
                            } elseif (!empty($context['chapter_id'])) {
                                $activeChapterId = $context['chapter_id'];
                            }

                            if (empty($activeChapterId) && $isFollowUp && isset($lastAssistantMsg)) {
                                $prevSource = $lastAssistantMsg->sources()->first();
                                if ($prevSource) {
                                    $prevChunk = DB::table('content_chunks as c')
                                        ->join('sections as s', 'c.section_id', '=', 's.id')
                                        ->where('c.id', $prevSource->content_chunk_id)
                                        ->select('s.chapter_id')
                                        ->first();
                                    if ($prevChunk && !empty($prevChunk->chapter_id)) {
                                        $activeChapterId = $prevChunk->chapter_id;
                                    }
                                }
                            }

                            if (!empty($understanding['is_excerpt'])) {
                                $emitActivity('searching', 'Analyzing excerpt from active chapter...');
                            } else {
                                $emitActivity('searching', 'Searching the Smart Adama knowledge base...');
                            }

                            $excerptText = $understanding['excerpt_text'] ?? null;
                            $candidates = $this->retriever->search(
                                $understanding['original_query'],
                                $searchQuery,
                                8,
                                $activeChapterId,
                                $excerptText
                            );
                            $logTiming('retrieval_search', ['candidate_count' => $candidates->count()]);

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
                            $logTiming('grounding_decision', ['selected_chunk_count' => count($chunks)]);
                        }
                    }

                    if (!isset($unresolved) || !$unresolved) {
                        $history = $session->messages()->whereNotIn('id', [$userMessage->id, $assistantMessage->id])->where('content', '!=', '')->orderBy('created_at')
                            ->get()->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->toArray();

                        $effectiveQuery = ($isFollowUp && !empty($searchQuery) && $searchQuery !== $query)
                            ? $searchQuery
                            : $query;

                        $emitActivity('preparing', 'Preparing your answer...');
                        $messages = $this->promptBuilder->buildMessages($history, $chunks, $effectiveQuery, $grounding, $isConversational, $responseMode, $context);
                        $logTiming('prompt_assembly');

                        $emitActivity('generating', 'Generating your response...');
                        $logTiming('provider_dispatch');

                        $streamAndEmit($messages);
                        $logTiming('stream_completion', ['chars' => strlen($responseContent)]);
                    }
                }
            }
        } catch (\App\Exceptions\RAGRetrievalException $e) {
            $code = $e->stage === 'database' ? 'DATABASE_ERROR' : 'RETRIEVAL_ERROR';
            Log::error("[RETRIEVAL_ERROR] ChatOrchestrator: RAG retrieval error", [
                'request_id' => $requestId,
                'session_id' => $session->id,
                'stage'      => $e->stage,
                'error'      => $e->getMessage()
            ]);
            $hasPartial = !empty(trim($responseContent));
            $emitError($code, 'We encountered an issue retrieving information from the knowledge base: ' . $e->getMessage(), $hasPartial);
        } catch (\App\Exceptions\AiProviderException $e) {
            $isVoyage = ($e->provider === 'voyage');
            $code = $isVoyage ? 'EMBEDDING_PROVIDER_ERROR' : 'LLM_PROVIDER_ERROR';
            $is429 = str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'quota');
            $isTimeout = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'Connection timed out');

            if ($is429) {
                Log::error("[QUOTA_EXHAUSTED] ChatOrchestrator: {$e->provider} quota limit hit", [
                    'request_id' => $requestId,
                    'session_id' => $session->id,
                    'provider'   => $e->provider,
                    'error'      => $e->getMessage()
                ]);
            } elseif ($isTimeout) {
                Log::error("[PROVIDER_TIMEOUT] ChatOrchestrator: {$e->provider} connection/read timed out", [
                    'request_id' => $requestId,
                    'session_id' => $session->id,
                    'provider'   => $e->provider,
                    'error'      => $e->getMessage()
                ]);
            } elseif ($isVoyage) {
                Log::error("[EMBEDDING_ERROR] ChatOrchestrator: Voyage embedding provider error", [
                    'request_id' => $requestId,
                    'session_id' => $session->id,
                    'provider'   => $e->provider,
                    'error'      => $e->getMessage()
                ]);
            } else {
                Log::error("[STREAM_PARSE_ERROR] ChatOrchestrator: LLM provider error", [
                    'request_id' => $requestId,
                    'session_id' => $session->id,
                    'provider'   => $e->provider,
                    'error'      => $e->getMessage()
                ]);
            }

            $hasPartial = !empty(trim($responseContent));
            $userMsg = $isTimeout
                ? 'The AI provider timed out while generating a response. Please try again.'
                : 'The AI provider is temporarily unavailable: ' . $e->getMessage();
            $emitError($code, $userMsg, $hasPartial);
        } catch (\Throwable $e) {
            Log::error('[STREAM_PARSE_ERROR] ChatOrchestrator: General stream error', [
                'request_id' => $requestId,
                'session_id' => $session->id,
                'error'      => $e->getMessage()
            ]);
            $hasPartial = !empty(trim($responseContent));
            $emitError('INTERNAL_ERROR', 'An unexpected error occurred during generation.', $hasPartial);
        }

        // Finalize: Preserve partial content if any tokens were emitted!
        $cleanContent = \App\Services\AI\ReasoningFilter::strip($responseContent);
        if (!empty(trim($cleanContent))) {
            DB::transaction(function () use ($assistantMessage, $cleanContent, $chunks, &$citations) {
                $assistantMessage->update(['content' => $cleanContent]);
                foreach ($chunks as $chunk) {
                    ChatMessageSource::create(['chat_message_id' => $assistantMessage->id, 'content_chunk_id' => $chunk['id'], 'similarity_score' => $chunk['rrf_score'] ?? 0]);
                }
                $citations = collect($chunks)->map(fn ($chunk) => [
                    'chunk_id' => $chunk['id'], 'page_number' => $chunk['page_number'] ?? 'Unknown',
                    'heading' => $chunk['structural_context']['heading'] ?? 'General Context',
                    'excerpt' => mb_substr($chunk['chunk_text'], 0, 200), 'rrf_score' => $chunk['rrf_score'] ?? 0,
                ])->toArray();
            });
            $emitComplete($assistantMessage->id, !empty($citations), $citations, $cleanContent);
        } else {
            $assistantMessage->delete();
        }

        $totalReqTime = round((microtime(true) - $reqStartTime) * 1000);
        Log::info("[AI_TIMING] request_id={$requestId} stage=total_duration total_ms={$totalReqTime}");
    }

    private function isPurelyAnaphoric(string $query): bool
    {
        $clean = rtrim(trim(mb_strtolower($query)), '?.!,;:');
        $anaphoricPhrases = [
            'why', 'why so', 'why is that', 'why not',
            'how', 'how so', 'how come', 'how does that work', 'how does it work',
            'tell me more', 'tell me more about it', 'tell me more about that',
            'explain that', 'explain it', 'explain this', 'explain more', 'explain further',
            'can you explain that', 'can you explain more',
            'give me an example', 'give an example',
            'expand', 'expand on that', 'expand on this',
            'go deeper', 'elaborate', 'elaborate please', 'can you elaborate',
            'what about it', 'what about that', 'what about this',
            'what does that mean', 'what does it mean', 'what do you mean',
            'and then', 'and why', 'what else',
            'yes', 'yeah', 'yep', 'yup', 'sure', 'ok', 'okay',
            'please', 'yes please', 'sure thing', 'go ahead', 'proceed', 'continue'
        ];

        return in_array($clean, $anaphoricPhrases, true);
    }
}