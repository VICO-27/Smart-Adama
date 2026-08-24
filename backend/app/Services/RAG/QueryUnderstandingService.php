<?php

namespace App\Services\RAG;

class QueryUnderstandingService
{
    private const ABBREVIATIONS = [
        'bg'  => 'background',
        'abt' => 'about',
        'info'=> 'information',
        'adma'=> 'Adama',
    ];

    public function analyze(string $query): array
    {
        $normalized = $this->normalize($query);
        $isConversational = $this->isConversational($normalized);
        $isEntityLookup = $this->isEntityLookup($normalized, $isConversational);
        $responseMode = $this->detectResponseMode($normalized);
        $isFollowUp = $this->isFollowUp($normalized);
        $chapterNumber = $responseMode === 'CHAPTER_SUMMARY' ? $this->extractChapterNumber($normalized) : null;

        if ($responseMode === 'CHAPTER_SUMMARY' && !$chapterNumber) {
            $responseMode = 'CHAPTER_SUMMARY_UNKNOWN';
        }

        return [
            'original_query'    => $query,
            'normalized_query'  => $normalized,
            'is_conversational' => $isConversational,
            'is_entity_lookup'  => $isEntityLookup,
            'response_mode'     => $responseMode,
            'is_follow_up'      => $isFollowUp,
            'chapter_number'    => $chapterNumber,
            'confidence'        => $normalized === $query ? 1.0 : 0.8,
        ];
    }

    private function normalize(string $query): string
    {
        $words = preg_split('/\s+/', trim($query));
        $normalizedWords = [];
        
        foreach ($words as $word) {
            $lower = mb_strtolower($word);
            if (isset(self::ABBREVIATIONS[$lower])) {
                // preserve case if possible, simple approach
                $normalizedWords[] = self::ABBREVIATIONS[$lower];
            } else {
                $normalizedWords[] = $word;
            }
        }

        return implode(' ', $normalizedWords);
    }

    private function isConversational(string $query): bool
    {
        // Must be very short and purely conversational.
        // It should NOT catch factual queries like "mayor" or "Adama".
        $query = trim(mb_strtolower($query));
        if (mb_strlen($query) > 50) return false;

        $greetings = ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'thanks', 'thank you', 'hi there', 'hello there'];
        $meta = ['who are you', 'summarize yourself', 'what can you do', 'help'];

        if (in_array($query, $greetings, true) || in_array($query, $meta, true)) {
            return true;
        }

        return false;
    }

    private function isEntityLookup(string $query, bool $isConversational): bool
    {
        if ($isConversational) {
            return false;
        }

        $words = count(preg_split('/\s+/', trim($query)));
        // E.g. "mayor", "Ashenafi Deresa", "who is hailu"
        if ($words <= 3) {
            return true;
        }
        
        if (preg_match('/^who is\s+/i', trim($query))) {
            return true;
        }

        return false;
    }

    private function detectResponseMode(string $query): string
    {
        $query = mb_strtolower(trim($query));

        $hasChapterKeyword = preg_match('/\b(chapter|ch\.?)\b/i', $query);
        if ($hasChapterKeyword) {
            if (preg_match('/\b(summarize|summary|about|explain|tell me about)\b/i', $query)) {
                if ($this->extractChapterNumber($query)) {
                    return 'CHAPTER_SUMMARY';
                }
                return 'CHAPTER_SUMMARY_UNKNOWN';
            }
        }

        if (preg_match('/^(please )?(summarize|give me a summary)/i', $query)) {
            return 'SUMMARY';
        }
        if (preg_match('/^(please )?(teach me deeply|explain deeply|explain thoroughly)/i', $query) || preg_match('/\b(in-depth)\b/i', $query)) {
            return 'DEEP';
        }
        if (preg_match('/^(please )?(explain in detail|tell me everything about)/i', $query) || preg_match('/\b(in detail)\b/i', $query)) {
            return 'DETAILED';
        }
        if (preg_match('/\b(step by step|how to|procedure)\b/i', $query)) {
            return 'STEP_BY_STEP';
        }
        if (preg_match('/\b(simply|simple|beginner|layman|dummy)\b/i', $query)) {
            return 'SIMPLE_EXPLANATION';
        }
        if (preg_match('/^(compare|difference between)/i', $query) || preg_match('/\b(vs|versus)\b/i', $query)) {
            return 'COMPARISON';
        }
        
        $wordCount = count(preg_split('/\s+/', $query));
        if ($wordCount <= 5 && preg_match('/^(what is|who is|where is|when is)\b/i', $query)) {
            return 'SHORT';
        }
        if (preg_match('/\b(short|brief)\b/i', $query)) {
            return 'SHORT';
        }

        return 'NORMAL';
    }

    private function extractChapterNumber(string $query): ?int
    {
        $query = mb_strtolower($query);
        $map = [
            'one' => 1, 'first' => 1,
            'two' => 2, 'second' => 2,
            'three' => 3, 'third' => 3,
            'four' => 4, 'fourth' => 4,
            'five' => 5, 'fifth' => 5,
            'six' => 6, 'sixth' => 6,
            'seven' => 7, 'seventh' => 7,
            'eight' => 8, 'eighth' => 8,
            'nine' => 9, 'ninth' => 9,
            'ten' => 10, 'tenth' => 10,
            'eleven' => 11, 'eleventh' => 11,
            'twelve' => 12, 'twelfth' => 12,
        ];

        if (preg_match('/\b(?:chapter|ch\.?)\s*(\d+)\b/i', $query, $matches)) {
            return (int) $matches[1];
        }
        
        foreach ($map as $word => $num) {
            if (preg_match('/\b(?:chapter|ch\.?)\s*' . $word . '\b/i', $query) ||
                preg_match('/\b' . $word . '\s*(?:chapter|ch\.?)\b/i', $query)) {
                return $num;
            }
        }

        return null;
    }

    private function isFollowUp(string $query): bool
    {
        $query = mb_strtolower(trim($query));
        $followUpPhrases = [
            'tell me more', 'explain that', 'why?', 'how?', 
            'give me an example', 'expand', 'what about', 
            'why is that', 'explain more', 'go deeper',
            'explain it', 'what does that mean'
        ];

        foreach ($followUpPhrases as $phrase) {
            if (str_contains($query, $phrase)) {
                return true;
            }
        }
        
        if (preg_match('/^(why|how|explain|what about|and)\b/i', $query) && count(preg_split('/\s+/', $query)) <= 5) {
            return true;
        }

        return false;
    }
}
