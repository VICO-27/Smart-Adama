<?php

namespace App\Services\RAG;

class DocumentNormalizationService
{
    /**
     * Normalize equivalent Unicode forms safely.
     * Whitespace cleanup
     * Line-break repair
     * Hyphenated word repair
     * Invisible/control characters
     * Encoding cleanup
     */
    public function normalize(string $text): string
    {
        // 0. Ensure valid UTF-8 by stripping invalid byte sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // 1. Remove null bytes and control characters explicitly
        $text = str_replace("\0", '', $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // 2. Unicode Normalization (NFC)
        if (class_exists('Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C);
        }

        // 3. Normalize line endings to \n
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // 4. Whitespace cleanup (multiple spaces to single space, except newlines)
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // 5. Paragraph separation: Collapse 3+ newlines into exactly 2 (paragraph boundary)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // 6. Hyphen repair (e.g., "develop-\nment" -> "development")
        // Only if it's a lowercase letter before hyphen and lowercase letter after newline
        $text = preg_replace('/([a-z])- *\n *([a-z])/', '$1$2', $text);

        // 7. Line-break repair (join lines broken by PDF bounding boxes)
        // If a line doesn't end with punctuation or paragraph break, and next line starts with lowercase, join them with a space.
        $text = preg_replace('/([^.!?:\n]) *\n *([a-z])/', '$1 $2', $text);

        // 8. Fix replacement character corruption if present
        $text = str_replace('', '', $text);

        return trim($text);
    }

    /**
     * Remove repetitive page artifacts (headers/footers) across an array of pages.
     */
    public function removePageArtifacts(array $pages): array
    {
        // Extract first and last lines of each page to find common headers/footers
        $firstLinesCount = [];
        $lastLinesCount = [];

        foreach ($pages as $page) {
            $lines = explode("\n", trim($page));
            if (count($lines) > 0) {
                $firstLine = trim($lines[0]);
                if (strlen($firstLine) > 0) {
                    $firstLinesCount[$firstLine] = ($firstLinesCount[$firstLine] ?? 0) + 1;
                }

                $lastLine = trim($lines[count($lines) - 1]);
                if (strlen($lastLine) > 0) {
                    $lastLinesCount[$lastLine] = ($lastLinesCount[$lastLine] ?? 0) + 1;
                }
            }
        }

        $threshold = max(3, floor(count($pages) * 0.3)); // If it appears on 30% of pages, it's a header/footer

        $commonHeaders = array_filter($firstLinesCount, fn ($count) => $count >= $threshold);
        $commonFooters = array_filter($lastLinesCount, fn ($count) => $count >= $threshold);

        $cleanedPages = [];
        foreach ($pages as $page) {
            $lines = explode("\n", trim($page));

            // Remove matching header
            if (count($lines) > 0 && isset($commonHeaders[trim($lines[0])])) {
                array_shift($lines);
            }

            // Remove matching footer
            if (count($lines) > 0 && isset($commonFooters[trim($lines[count($lines) - 1])])) {
                array_pop($lines);
            }

            // Remove standalone page numbers (e.g., "Page 12", "- 12 -", "12")
            $lines = array_filter($lines, function ($line) {
                $line = trim($line);
                // Matches "12", "- 12 -", "Page 12", "12 / 50"
                if (preg_match('/^[-–\s]*(?:Page\s*)?\d+(?:\s*\/\s*\d+)?[-–\s]*$/i', $line)) {
                    return false;
                }

                return true;
            });

            $cleanedPages[] = implode("\n", $lines);
        }

        return $cleanedPages;
    }
}
