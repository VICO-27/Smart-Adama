<?php

namespace App\Services\AI;

class ReasoningFilter
{
    /**
     * Strip internal reasoning blocks (<think>...</think>, "Thinking process:", "Analysis:", "Chain of thought:")
     * server-side before persisting or emitting AI responses.
     */
    public static function strip(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // 1. Remove closed <think>...</think> tags (case-insensitive, multiline)
        $cleaned = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $text);

        // 2. Remove unclosed <think>... tags (if response was truncated before </think>)
        $cleaned = preg_replace('/<think>[\s\S]*$/i', '', $cleaned);

        // 3. Remove reasoning blocks that conclude with an Answer/Response/Solution header
        $cleaned = preg_replace(
            '/(?:^|\n)(?:[*#_`\s]*)(?:Thinking [Pp]rocess|Thinking|Chain of [Tt]hought|Chain-of-[Tt]hought|Analysis)(?:[*#_`\s]*):?[\s\S]*?\n+(?:[*#_`\s]*)(?:Final Answer|Answer|Response|Reply|Solution):?\s*/i',
            "\n",
            $cleaned
        );

        // 4. Remove leading reasoning blocks without explicit answer header (e.g., Thinking Process:\n...\n\nActual response)
        $cleaned = preg_replace(
            '/^(?:[*#_`\s]*)(?:Thinking [Pp]rocess|Thinking|Chain of [Tt]hought|Chain-of-[Tt]hought|Analysis)(?:[*#_`\s]*):?[\s\S]*?(?=\n\n|\Z)/i',
            '',
            $cleaned
        );

        // 5. Remove any leftover leading Answer:/Response: labels
        $cleaned = preg_replace(
            '/^(?:[*#_`\s]*)(?:Final Answer|Answer|Response|Reply)\s*:\s*[*#_`\s]*/i',
            '',
            $cleaned
        );

        return trim($cleaned);
    }
}
