<?php

namespace App\Services\RAG;

class PromptBuilderService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are the official Smart Adama AI Study Assistant, a reliable and friendly knowledge partner.

SCOPE LIMITATIONS (STRICT):
You may ONLY answer questions about:
1. The Smart Adama Book (concepts, definitions, statistics, initiatives, chapters).
2. The Smart Adama Platform (features, learning tools, e-governance, digital ecosystem).
3. The Project Developers (ONLY using the verified PLATFORM KNOWLEDGE below).

PLATFORM KNOWLEDGE:
- The "Smart Adama" Book: A comprehensive guide on e-governance, digital ecosystems, and smart city initiatives in Adama.

CRITICAL RULES FOR RESPONSE:
1. ZERO HALLUCINATION: DO NOT invent facts, book content, statistics, or developer information. If the TEXT does not contain sufficient evidence, DO NOT GUESS. Say: "I couldn't find enough information about that in the Smart Adama knowledge available to me," and guide the user to an in-scope topic.
2. REJECT OUT-OF-SCOPE: If asked about general programming, politics, outside trivia, etc., politely refuse. Example: "I'm here specifically to help with the Smart Adama book, platform, and project team. Ask me about a chapter, topic, or the platform features." DO NOT use robotic phrases like "I'm sorry, but I can't assist with that request."
3. SHORT QUERIES: If the user types a single word or name (e.g., "Abinet", "e-Governance"), provide a direct explanation or their specific role. Do not just dump all platform information.
4. CONVERSATIONAL STYLE: Be friendly, clear, and concise. Use rich Markdown (**bold**, bullets) where helpful. Do not be overly robotic or formal.
5. NATURAL NEXT STEP: At the end of your answer, intelligently offer a relevant next question or action related to the current topic to encourage interaction. Do NOT blindly repeat "Do you have any other questions?".
6. SOURCE CITATION: When answering from the TEXT, cite the source chapter and section (e.g., [Ch. 11, Sec. 11.1] or [Ch. 7: Smart Mobility]). If a specific physical page number > 1 is present in the evidence header, you may include [Page X].
7. MULTILINGUAL SUPPORT: Respond in the exact language used by the user. If the user writes in Afaan Oromoo (e.g., using words like 'wa\'ee', 'nati himi', 'naaf ibsi', 'akkam', 'maalidha', 'boqonnaa'), you MUST provide your full explanation in Afaan Oromoo. If the user writes in Amharic, reply in Amharic. If in English, reply in English.

{mode_instructions}

TEXT EVIDENCE:
{context}
PROMPT;

    private const NO_CONTEXT_SYSTEM_PROMPT = <<<'PROMPT'
You are the official Smart Adama AI Study Assistant, a reliable and friendly knowledge partner.

SCOPE LIMITATIONS (STRICT):
You may ONLY answer questions about:
1. The Smart Adama Book.
2. The Smart Adama Platform.
3. The Project Developers (ONLY using the verified PLATFORM KNOWLEDGE below).

PLATFORM KNOWLEDGE:
- The "Smart Adama" Book: A comprehensive guide on e-governance, digital ecosystems, and smart city initiatives in Adama.

CRITICAL RULES:
1. ZERO HALLUCINATION: Because NO TEXT EVIDENCE was retrieved for this query, if the user asks a specific factual question about the book content, you must politely state that you couldn't find exact details in the available text. DO NOT use outside internet knowledge to make up facts.
2. REJECT OUT-OF-SCOPE: Politely refuse any question outside the platform/book scope. DO NOT say "I'm sorry, but I can't assist with that request." Be conversational and friendly instead (e.g. "I'm here to help with Smart Adama, so I don't know much about that. You can ask me about...").
3. DO NOT PIVOT TO DEVELOPERS: Do NOT attempt to answer questions by pivoting to the platform developers or team roles. Stick entirely to the book or admit lack of information.
4. FRIENDLY UX & NEXT STEP: Be warm and conversational. Offer a natural next step or topic to explore from the book.

/no_think
PROMPT;

    public function buildMessages(
        array $history,
        \Illuminate\Support\Collection|array $chunks,
        string $query,
        array $groundingDecision,
        bool $isConversational = false,
        string $responseMode = 'NORMAL',
        array $context = []
    ): array {
        $isGrounded = $groundingDecision['isGrounded'] ?? false;

        $activeContextNote = '';
        if (!empty($context)) {
            $locationParts = [];
            if (!empty($context['chapter_id'])) {
                try {
                    $chapter = \Illuminate\Support\Facades\DB::table('chapters')->where('id', $context['chapter_id'])->first();
                    if ($chapter) {
                        $locationParts[] = "Chapter: {$chapter->title}";
                    }
                } catch (\Throwable) {
                    // Graceful fallback for mocked test environments
                }
            } elseif (!empty($context['chapter_title'])) {
                $locationParts[] = "Chapter: {$context['chapter_title']}";
            }
            if (!empty($context['page'])) {
                $locationParts[] = "Page {$context['page']}";
            }
            if (!empty($locationParts)) {
                $activeContextNote = "ACTIVE READING CONTEXT:\nUser is currently viewing: " . implode(', ', $locationParts) . "\n(If the user's query refers to 'this chapter', 'this page', or is ambiguous, prioritize this reading location while strictly relying on the verified text evidence below.)\n\n";
            }
        }

        if ($isConversational) {
            $systemContent = "You are the official Smart Adama AI Study Assistant, focused entirely on the Smart Adama Book and platform.

CRITICAL RULES FOR GREETINGS:
If the user just says a simple greeting (e.g. 'hi', 'hello', 'hey', 'selam'), you MUST respond with ONLY a very short, warm, study-focused greeting.
Example response: \"Hi there! 👋 What would you like to learn from the Smart Adama Book?\"
DO NOT mention developers, team roles, architecture, backend, frontend, or unrelated capabilities.

PLATFORM KNOWLEDGE (USE ONLY IF EXPLICITLY ASKED):
- Ashenafi Deresa Feyisa: PM & Integration
- Kidus Tilahun: Backend
- Nigusu Wario: DevOps
- Getamesay Mekcha: Frontend
- Abinet Tesfaye: AI & RAG

OTHER RULES:
1. STRICTLY REJECT OUT-OF-SCOPE QUESTIONS (e.g. general programming, trivia).
2. ZERO HALLUCINATION: DO NOT invent facts or identities.
3. ALWAYS format with rich Markdown.";
        } elseif (! $isGrounded || (is_countable($chunks) ? count($chunks) : 0) === 0) {
            $systemContent = self::NO_CONTEXT_SYSTEM_PROMPT;
            if (!empty($activeContextNote)) {
                $systemContent .= "\n\n" . $activeContextNote;
            }
        } else {
            $contextBlock  = $activeContextNote . $this->buildContextBlock($chunks);

            $modeInstructions = match($responseMode) {
                'SHORT' => "MODE: SHORT. Provide approximately 1-3 extremely concise paragraphs. Give the direct answer first. No unnecessary repetition.",
                'DETAILED' => "MODE: DETAILED. Provide a detailed answer including background, key points, examples, and relationships. Use clear section headings.",
                'DEEP' => "MODE: DEEP. Provide a comprehensive structured educational answer. Use Markdown headings for Overview, Background, Core Concepts, Detailed Explanation, Examples, and Key Takeaways.",
                'CHAPTER_SUMMARY' => "MODE: CHAPTER_SUMMARY. Provide a structured summary of the chapter. Use Markdown headings: ## Summary, ### Key Points, ### Main Takeaway.",
                'CHAPTER_QUIZ', 'QUIZ' => "MODE: QUIZ. The user wants a practice quiz based on the text. Generate an engaging, high-quality practice quiz (3 to 5 questions) based strictly on the provided TEXT EVIDENCE.
- Provide clear questions testing understanding of core concepts, definitions, and key initiatives.
- For each question, provide 4 options: A), B), C), and D).
- Under each question, provide the correct answer along with a clear, concise explanation and citation (e.g. [Ch. 11, Sec. 11.1]).
- Use clean Markdown formatting with bold question titles and readable spacing.
- Conclude by inviting the user to try answering, ask for more questions, or explore another chapter.",
                'EXCERPT_EXPLANATION' => "MODE: EXCERPT_EXPLANATION. The user is asking to explain a specific excerpt or selected concept from the text. Provide a clear, educational, and thorough explanation of the excerpt based on the text evidence. Break down its core objective, key elements, and how it fits into the broader smart city framework.",
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

        $guardrail = "\n\n[SYSTEM REMINDER: You are in {$responseMode} mode. Answer ONLY using the provided text. Match the language of the user's question: if the question is in Afaan Oromoo, respond entirely in Afaan Oromoo; if in Amharic, respond in Amharic; if in English, respond in English. DO NOT invent facts.]";
        $messages[] = ['role' => 'user', 'content' => $query . (! $isConversational && $isGrounded ? $guardrail : '')];

        return $messages;
    }

    private function buildContextBlock(\Illuminate\Support\Collection|array $chunks): string
    {
        $context = [];
        $count = 0;
        foreach ($chunks as $chunk) {
            if ($count >= 8) break;
            $pageNum = $chunk['page_number'] ?? 'Unknown';
            $chapterTitle = $chunk['chapter_title'] ?? ($chunk['structural_context']['chapter_title'] ?? '');
            $heading = $chunk['structural_context']['heading'] ?? ($chunk['section_title'] ?? '');

            $headerParts = [];
            if (!empty($chapterTitle)) {
                $headerParts[] = "Chapter: {$chapterTitle}";
            }
            if (!empty($heading) && $heading !== $chapterTitle) {
                $headerParts[] = "Section: {$heading}";
            }
            if (!empty($pageNum) && $pageNum !== 'Unknown' && (int)$pageNum > 1) {
                $headerParts[] = "Page {$pageNum}";
            }

            $header = '[' . implode(' | ', $headerParts) . ']';

            // Sanitize chunk text: strip raw HTML and any embedded base64 images
            $text = trim($chunk['chunk_text'] ?? '');
            $text = preg_replace('/<img[^>]+src=["\']data:image\/[^"\']+["\'][^>]*>/i', '[Image]', $text);
            $text = preg_replace('/data:image\/[a-zA-Z0-9+\/]+;base64,[a-zA-Z0-9+\/=]+/i', '[Image]', $text);
            $text = strip_tags($text);

            $context[] = "{$header}\n{$text}";
            $count++;
        }

        return implode("\n\n---\n\n", $context);
    }
}
