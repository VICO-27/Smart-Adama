<?php

namespace App\Services\RAG;

/**
 * Smart semantic chunking service for Phase 3.
 * Splits document pages by structural boundaries (paragraphs, then sentences),
 * respecting a maximum chunk size with a slight overlap.
 */
class DocumentChunkingService
{
    private int $targetTokens;
    private float $overlapRatio;
    private const CHARS_PER_TOKEN = 4; // Approx 4 chars = 1 token for Qwen/general LLMs

    public function __construct()
    {
        $this->targetTokens = (int) config('ai.rag.chunk_target_tokens', 700);
        $this->overlapRatio = (float) config('ai.rag.chunk_overlap_ratio', 0.15);
    }

    /**
     * Chunk text semantically.
     * 
     * @param string $text The text to chunk
     * @param array $metadata Extra metadata to attach to the chunk (e.g. book_id, page_number, structural_context)
     * @return array Array of chunk data
     */
    public function chunk(string $text, array $metadata): array
    {
        if (empty(trim($text))) {
            return [];
        }

        $targetChars = $this->targetTokens * self::CHARS_PER_TOKEN;
        $overlapChars = (int) ($targetChars * $this->overlapRatio);

        // 1. Split by paragraphs (\n\n)
        $paragraphs = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $currentChunk = '';
        
        $chunkIndex = 0;

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;

            $paraLength = strlen($paragraph);

            // If a single paragraph is too large, split it by sentences
            if ($paraLength > $targetChars) {
                $sentences = $this->splitSentences($paragraph);
                
                foreach ($sentences as $sentence) {
                    $sentLength = strlen($sentence);
                    
                    if (strlen($currentChunk) + $sentLength > $targetChars && !empty($currentChunk)) {
                        $chunks[] = $this->buildChunk($currentChunk, $chunkIndex++, $metadata);
                        $currentChunk = $this->getOverlapTail($currentChunk, $overlapChars);
                    }
                    
                    $currentChunk .= (empty($currentChunk) ? '' : ' ') . $sentence;
                }
                continue;
            }

            // Normal paragraph merging
            if (strlen($currentChunk) + $paraLength + 2 > $targetChars && !empty($currentChunk)) {
                $chunks[] = $this->buildChunk($currentChunk, $chunkIndex++, $metadata);
                $currentChunk = $this->getOverlapTail($currentChunk, $overlapChars);
            }
            
            $currentChunk .= (empty($currentChunk) ? '' : "\n\n") . $paragraph;
        }

        if (!empty(trim($currentChunk))) {
            $chunks[] = $this->buildChunk($currentChunk, $chunkIndex++, $metadata);
        }

        return $chunks;
    }

    private function splitSentences(string $text): array
    {
        $parts = preg_split(
            '/(?<=[.!?])\s+(?=[A-Z\p{Lu}])/u',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        return array_values(array_filter(
            $parts,
            fn ($s) => mb_strlen(trim($s)) > 0
        ));
    }

    private function getOverlapTail(string $text, int $overlapChars): string
    {
        if (strlen($text) <= $overlapChars) {
            return $text;
        }

        // Try to find a sentence boundary within the tail
        $tail = substr($text, -$overlapChars);
        $boundary = preg_match('/[.!?]\s+([A-Z\p{Lu}].*)$/u', $tail, $matches, PREG_OFFSET_CAPTURE);
        
        if ($boundary && isset($matches[1])) {
            // Return from the start of the next sentence
            return $matches[1][0];
        }

        // Fallback: just return the raw characters (cut mid-sentence)
        return $tail;
    }

    private function buildChunk(string $text, int $index, array $metadata): array
    {
        $text = trim($text);
        $tokenCount = (int) ceil(strlen($text) / self::CHARS_PER_TOKEN);

        return array_merge([
            'chunk_text'  => $text,
            'chunk_index' => $index,
            'token_count' => $tokenCount,
        ], $metadata);
    }
}
