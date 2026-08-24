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
    public function resolve(string $currentQuery, ?string $lastUserMessage, ?string $lastAssistantMessage): ?string
    {
        if (!$lastUserMessage) {
            return null; // No context to resolve from
        }

        $prompt = <<<PROMPT
You are a query resolution engine.
Given the previous conversation, rewrite the user's latest follow-up question into a complete, standalone question that includes the necessary subject/topic.
If the user's latest question changes the topic completely, or if it is impossible to determine what "that/it/he/she" refers to, output EXACTLY: UNRESOLVED

Conversation:
User: {$lastUserMessage}
Assistant: {$lastAssistantMessage}

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
}
