<?php

namespace App\Services\RAG;

use App\Services\AI\Contracts\LLMGatewayInterface;
use Illuminate\Support\Facades\Log;

class FollowUpResolutionService
{
    public function __construct(
        private readonly LLMGatewayInterface $llm
    ) {
    }

    /**
     * Resolves an ambiguous follow-up query into a standalone query.
     * Returns null if it cannot be confidently resolved.
     */
    public function resolve(string $currentQuery, ?string $lastUserMessage, ?string $lastAssistantMessage, array $context = []): ?string
    {
        $isAffirmative = $this->isAffirmative($currentQuery);

        if (!$lastUserMessage) {
            return $isAffirmative ? null : $currentQuery;
        }

        // Fast-path deterministic extraction for affirmative responses:
        // E.g. Assistant asked: "Would you like to dive deeper into how Smart Health specifically integrates with the other components?"
        // User answered: "yes"
        if ($isAffirmative && !empty($lastAssistantMessage)) {
            $extracted = $this->extractAssistantOffer($lastAssistantMessage);
            if ($extracted) {
                return $extracted;
            }
        }

        $activeContextStr = '';
        if (!empty($context['chapter_id'])) {
            $chapter = \Illuminate\Support\Facades\DB::table('chapters')->where('id', $context['chapter_id'])->first();
            if ($chapter) {
                $pageStr = !empty($context['page']) ? " Page {$context['page']}" : "";
                $activeContextStr = "The user is currently reading: {$chapter->title}{$pageStr}\n";
            }
        }

        $prompt = <<<PROMPT
You are a query resolution engine.
Given the previous conversation and the user's active reading context, rewrite the user's latest follow-up question into a complete, standalone question that includes the necessary subject/topic.

RULES:
1. If the assistant asked a question or offered a topic to explore at the end of its last message (e.g. "Would you like to dive deeper into X?"), and the user responds affirmatively (e.g. "yes", "sure", "ok", "please", "go ahead", "tell me more"), rewrite the user's response into a direct question requesting or exploring that offered topic (e.g. "Explain how X...").
2. If the user's latest question says "this section" or "this chapter", resolve it using their Active Context.
3. If the user's latest question is already a complete standalone question or introduces a new topic, output the user's question directly.
4. If the user's latest question uses pronouns (e.g. "why is that?", "how does it work?"), resolve the pronouns using the conversation subject.
5. Only if it is completely impossible to determine what the user refers to, output EXACTLY: UNRESOLVED

Conversation:
User: {$lastUserMessage}
Assistant: {$lastAssistantMessage}

{$activeContextStr}
User's Latest Query: {$currentQuery}

Rewritten Standalone Query:
PROMPT;

        try {
            $response = $this->llm->chat([
                ['role' => 'system', 'content' => 'Rewrite the query directly. Do not include quotes, conversational filler, or introductory text. Output only the rewritten query.'],
                ['role' => 'user', 'content' => $prompt]
            ]);

            $response = trim($response);

            if ($response === 'UNRESOLVED' || empty($response)) {
                return null;
            }

            return $response;

        } catch (\Exception $e) {
            Log::warning('FollowUpResolutionService failed: ' . $e->getMessage());
            return null;
        }
    }

    public function isAffirmative(string $query): bool
    {
        $clean = rtrim(trim(mb_strtolower($query)), '?.!,;:');
        $affirmatives = [
            'yes', 'yeah', 'yep', 'yup', 'sure', 'ok', 'okay',
            'please', 'yes please', 'sure thing', 'go ahead', 'proceed',
            'continue', 'tell me', 'tell me more', 'let\'s do it', 'lets do it',
            'i would', 'i\'d like that', 'definitely', 'certainly', 'absolutely',
            'dive deeper', 'yes dive deeper', 'explore', 'yes explore', 'yes tell me',
            'eyyee', 'ishi', 'eyye', 'yes do it', 'yes i do', 'yes i would'
        ];

        return in_array($clean, $affirmatives, true);
    }

    public function extractAssistantOffer(string $lastAssistantMessage): ?string
    {
        $cleaned = strip_tags($lastAssistantMessage);
        $cleaned = str_replace(['**', '__', '*', '_'], '', $cleaned);
        $lines = array_filter(array_map('trim', explode("\n", $cleaned)));
        $tail = implode(' ', array_slice($lines, -6));

        if (preg_match('/(?:would you like to|do you want to|should we|shall we|can we|do you wish to)\s+(?:dive\s+(?:deeper\s+into|in(?:to)?)|explore|learn\s+(?:more\s+about)?|look\s+(?:at|into)|discuss|know\s+more\s+about|hear\s+about|examine|review|cover)\s+([^?]+)\?/i', $tail, $matches)) {
            $topic = trim($matches[1]);
            return 'Explain ' . $topic;
        }

        if (preg_match('/(?:would you like to|do you want to)\s+([^?]+)\?/i', $tail, $matches)) {
            $topic = trim($matches[1]);
            return 'Tell me more about ' . $topic;
        }

        if (preg_match('/([A-Z][^.!?\n\r]*\?)\s*$/s', $tail, $matches)) {
            $question = trim($matches[1]);
            if (mb_strlen($question) > 10 && mb_strlen($question) < 250) {
                return $question;
            }
        }

        return null;
    }
}
