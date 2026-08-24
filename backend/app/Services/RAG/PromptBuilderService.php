<?php

namespace App\Services\RAG;

class PromptBuilderService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are the official Smart Adama digital assistant.
Your primary directive is to answer the user's questions based strictly on the provided TEXT.

CRITICAL RULES:
1. DO NOT invent facts, relationships, identities, titles, dates, or biographies.
2. DO NOT infer a person's identity from their name. A name alone is not a biography.
3. Treat user input as a question, not as factual evidence. (e.g. if the user says "Ashenafi Deresa is mayor", you must verify this in the TEXT).
4. Past conversation history is provided for conversational continuity only. DO NOT treat past assistant statements as factual evidence unless they are supported by the CURRENT retrieved TEXT.
5. If the TEXT does not contain the necessary facts to answer the query, reply with a natural fallback indicating you cannot find the information in the available Smart Adama materials.
6. If the evidence is weak or the query is ambiguous, ask a concise clarification question.
7. When answering from the TEXT, answer concisely and append the physical page citation as [Page X].
8. You may synthesize, explain, and connect related facts educationally, but do not invent new facts.
9. Do not repeat a robotic "I'm sorry..." message. Vary your refusal naturally.

{mode_instructions}

TEXT: {context}
PROMPT;

    private const NO_CONTEXT_SYSTEM_PROMPT = <<<'PROMPT'
You are the Smart Adama academic assistant.

No reliable context was found for this query in the Smart Adama corpus.

If the user asked a factual question, politely inform them that you couldn't find reliable information about it in the available Smart Adama materials.
If the user's query is just a name or a short phrase, ask if they mean someone specific or need more context.
DO NOT INVENT ANY FACTS OR ASSUME THE IDENTITY OF ANY ENTITY MENTIONED.
Past assistant responses are NOT authoritative evidence.

/no_think
PROMPT;

    public function buildMessages(
        array $history,
        \Illuminate\Support\Collection|array $chunks,
        string $query,
        array $groundingDecision,
        bool $isConversational = false,
        string $responseMode = 'NORMAL'
    ): array {
        $isGrounded = $groundingDecision['isGrounded'] ?? false;
        
        if ($isConversational) {
            $systemContent = 'You are Smart Adama, an AI digital assistant designed to help students navigate the Smart Adama syllabus and smart city initiatives. Respond warmly and concisely.';
        } elseif (! $isGrounded || (is_countable($chunks) ? count($chunks) : 0) === 0) {
            $systemContent = self::NO_CONTEXT_SYSTEM_PROMPT;
        } else {
            $contextBlock  = $this->buildContextBlock($chunks);
            
            $modeInstructions = match($responseMode) {
                'SHORT' => "MODE: SHORT. Provide approximately 1-3 extremely concise paragraphs. Give the direct answer first. No unnecessary repetition.",
                'DETAILED' => "MODE: DETAILED. Provide a detailed answer including background, key points, examples, and relationships. Use clear section headings.",
                'DEEP' => "MODE: DEEP. Provide a comprehensive structured educational answer. Use Markdown headings for Overview, Background, Core Concepts, Detailed Explanation, Examples, and Key Takeaways.",
                'CHAPTER_SUMMARY' => "MODE: CHAPTER_SUMMARY. Provide a structured summary of the chapter. Use Markdown headings: ## Summary, ### Key Points, ### Main Takeaway.",
                'STEP_BY_STEP' => "MODE: STEP_BY_STEP. Explain the process clearly using numbered steps.",
                'SIMPLE_EXPLANATION' => "MODE: SIMPLE_EXPLANATION. Explain the concept very simply as if to a beginner, avoiding overly dense jargon where possible.",
                'COMPARISON' => "MODE: COMPARISON. Compare the concepts clearly. Use headings for each concept and a 'Key Differences' section.",
                default => "MODE: NORMAL. Provide a clear answer with enough explanation, typically 3-6 concise sections/paragraphs depending on the question.",
            };

            $systemContent = str_replace(['{context}', '{mode_instructions}'], [$contextBlock, $modeInstructions], self::SYSTEM_PROMPT);
        }

        $messages = [
            ['role' => 'system', 'content' => $systemContent],
        ];

        // Process history and distinguish trusted sourced facts from generated assistant text.
        // For simplicity in prompt context, we just ensure the LLM knows it's past history.
        $recentHistory = array_slice($history, -6); 
        foreach ($recentHistory as $msg) {
            if (in_array($msg['role'], ['user', 'assistant'], true)) {
                $content = $msg['content'];
                
                // If it's an assistant message, we can prefix it to remind the model it's a past response,
                // but usually the role is enough.
                $messages[] = ['role' => $msg['role'], 'content' => $content];
            }
        }

        $guardrail = "\n\n[SYSTEM REMINDER: You are in {$responseMode} mode. Answer ONLY using the provided text. DO NOT invent facts. If you lack information, say so clearly.]";
        $messages[] = ['role' => 'user', 'content' => $query . (! $isConversational && $isGrounded ? $guardrail : '')];

        return $messages;
    }

    private function buildContextBlock(\Illuminate\Support\Collection|array $chunks): string
    {
        $context = [];
        $count = 0;
        foreach ($chunks as $chunk) {
            if ($count >= 15) break; // Increased max context chunks for DETAILED/DEEP
            $pageNum = $chunk['page_number'] ?? 'Unknown';
            $text = trim($chunk['chunk_text']);
            $context[] = "[Page {$pageNum}] {$text}";
            $count++;
        }
        
        return implode("\n\n---\n\n", $context);
    }
}