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
        $chapterNumber = $this->extractChapterNumber($normalized);
        $excerptInfo = $this->extractExcerptInfo($query);

        $responseMode = $excerptInfo['is_excerpt'] ? 'EXCERPT_EXPLANATION' : $this->detectResponseMode($normalized, $chapterNumber);
        $isAffirmative = $this->isAffirmative($normalized);
        $isFollowUp = $this->isFollowUp($normalized);

        $searchQuery = ($excerptInfo['is_excerpt'] && !empty($excerptInfo['excerpt_text']))
            ? $excerptInfo['excerpt_text']
            : $this->cleanForSearch($normalized, $chapterNumber);

        return [
            'original_query'    => $query,
            'normalized_query'  => $searchQuery,
            'is_conversational' => $isConversational,
            'is_entity_lookup'  => $isEntityLookup,
            'is_affirmative'    => $isAffirmative,
            'response_mode'     => $responseMode,
            'is_follow_up'      => $isFollowUp,
            'chapter_number'    => $chapterNumber ?? $excerptInfo['chapter_number'],
            'page_number'       => $excerptInfo['page_number'],
            'is_excerpt'        => $excerptInfo['is_excerpt'],
            'excerpt_text'      => $excerptInfo['excerpt_text'],
            'confidence'        => $normalized === $query ? 1.0 : 0.8,
        ];
    }

    private function cleanForSearch(string $query, ?int $chapterNumber): string
    {
        $clean = $query;
        // Strip parenthetical chapter indicators
        $clean = preg_replace('/\((?:boqonnaa|boqonna|chapter|ch|kutaa|ምዕራፍ)\s*\d+\)/iu', '', $clean);
        // Strip Afaan Oromoo and Amharic framing wrappers
        $clean = preg_replace('/\b(?:wa\'ee|waaye|waan|nati\s+himi|naaf\s+himi|naaf\s+ibsi|maalidha|maali|akkamitti|akkam|boqonnaa|boqonna|kutaa|mee|deebisi)\b/iu', ' ', $clean);
        if (preg_match('/[a-zA-Z]{3,}/', $clean)) {
            $clean = preg_replace('/[\x{1200}-\x{137F}]+/u', ' ', $clean);
        }
        $clean = trim(preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $clean));
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        // If nothing substantial remains but we know the chapter number, provide the canonical English title
        if (empty($clean) || strlen($clean) < 3) {
            $chapterTopics = [
                1  => 'introduction smart city',
                2  => 'smart governance',
                3  => 'digital adama',
                4  => 'smart security',
                5  => 'smart urban design land use management',
                6  => 'smart environment organic production',
                7  => 'smart mobility',
                8  => 'smart social services',
                9  => 'smart tourism and culture',
                10 => 'smart public relation knowledge management',
                11 => 'smart people',
            ];
            if ($chapterNumber !== null && isset($chapterTopics[$chapterNumber])) {
                return $chapterTopics[$chapterNumber];
            }
            return $query;
        }

        return $clean;
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

        $greetings = ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'thanks', 'thank you', 'hi there', 'hello there', 'selam', 'akkam', 'tena yistelegn', 'tadias'];
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

        if ($this->isAffirmative($query) || $this->isFollowUp($query)) {
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

    private function detectResponseMode(string $query, ?int $chapterNumber = null): string
    {
        $query = mb_strtolower(trim($query));

        // Quiz / practice questions detection
        if (preg_match('/\b(quiz|test me|test my knowledge|practice questions?|exam questions?|trivia|multiple choice|mcq|mcqs)\b/i', $query)) {
            $chapterNum = $chapterNumber ?? $this->extractChapterNumber($query);
            if ($chapterNum !== null) {
                return 'CHAPTER_QUIZ';
            }
            if (preg_match('/^(?:can you\s+)?(?:please\s+)?(?:give me|generate|make|create|start)?\s*(?:a\s+)?(?:quiz|test|trivia)\s*$/i', $query)) {
                return 'CHAPTER_QUIZ_UNKNOWN';
            }
            return 'QUIZ';
        }

        $hasChapterKeyword = preg_match('/\b(chapter|ch\.?|boqonnaa|boqonna|kutaa|ምዕራፍ)\b/iu', $query);
        $hasExcerptKeyword = preg_match('/\b(excerpt|quote)\b/i', $query);
        $chapterNum = $chapterNumber ?? $this->extractChapterNumber($query);

        if ($chapterNum !== null && !$hasExcerptKeyword) {
            // If explicit chapter keyword is used (e.g. "chapter 11", "boqonnaa 7", "Smart Mobility (Boqonnaa 7)", "summarize chapter 11")
            if ($hasChapterKeyword) {
                if (preg_match('/\b(summarize|summary|about|explain|tell me about|dive into|dive in|explore|read|teach|learn|start|discuss|overview|review|cover|what is in|what is|wa\'ee|waaye|waan|nati\s+himi|naaf\s+himi|naaf\s+ibsi|maalidha|maali|ibsa|ስለ|ንገረኝ|አስረዳኝ|ምንድን\s*ነው|ምንድነው)\b/iu', $query)
                    || preg_match('/^(?:let\'?s\s+)?(?:dive|explore|read|start|open|discuss)\b/i', $query)
                    || preg_match('/^(?:chapter|ch\.?|boqonnaa|boqonna|kutaa|ምዕራፍ)\s*\d+$/iu', $query)
                    || preg_match('/^[a-z\s]+(?:\s*\((?:boqonnaa|boqonna|chapter|ch|kutaa|ምዕራፍ)\s*\d+\))?$/iu', $query)) {
                    return 'CHAPTER_SUMMARY';
                }
            } else {
                // No explicit "chapter" keyword: check if the entire query is asking about the initiative itself
                // (e.g. "smart people", "tell me about smart people", "wa'ee smart people nati himi", "smart mobility", "summarize smart mobility")
                $stripped = preg_replace('/\b(?:please\s+)?(?:can you\s+)?(?:tell me\s+(?:about|more about)?|explain|summarize|summary of|what is|overview of|dive into|explore|wa\'ee|waaye|waan|nati\s+himi|naaf\s+himi|naaf\s+ibsi|maalidha|maali|ibsa|ስለ|ንገረኝ|አስረዳኝ|ምንድን\s*ነው|ምንድነው|eyyee|eeyyee|yes|ok|okay)\b/iu', '', $query);
                $stripped = trim(preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $stripped));
                $stripped = trim(preg_replace('/\s+/', ' ', $stripped));

                $isPureInitiative = false;
                $initiativePhrases = [
                    'smart people', 'smart mobility', 'smart transportation', 'smart security', 'smart governance',
                    'digital adama', 'smart environment', 'organic production', 'smart urban design', 'land use management',
                    'smart social services', 'smart social service', 'smart tourism and culture', 'smart tourism',
                    'smart public relation', 'smart public relations', 'knowledge management'
                ];
                foreach ($initiativePhrases as $ip) {
                    if (strcasecmp($stripped, $ip) === 0) {
                        $isPureInitiative = true;
                        break;
                    }
                }

                if ($isPureInitiative) {
                    return 'CHAPTER_SUMMARY';
                }
            }
        } elseif ($hasChapterKeyword && !$hasExcerptKeyword) {
            if (preg_match('/\b(summarize|summary|about|explain|tell me about|dive into|explore|read)\b/iu', $query)) {
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

    public function extractExcerptInfo(string $query): array
    {
        $isExcerpt = false;
        $excerptText = null;
        $chapterNumber = null;
        $pageNumber = null;

        if (preg_match('/\b(excerpt|quote|selection|this section|this paragraph)\b/i', $query)) {
            $isExcerpt = true;

            if (preg_match('/\bpage\s*(\d+)\b/i', $query, $pMatch)) {
                $pageNumber = (int) $pMatch[1];
            }

            if (preg_match('/\b(?:chapter|ch)[-.\s]*(\d+)\b/i', $query, $cMatch)) {
                $chapterNumber = (int) $cMatch[1];
            }

            // Extract quoted blocks: e.g. "Ch-5: Smart Urban Design and Land Use Management", page 6?\n\n"Smart Local Development Plan"
            if (preg_match_all('/"([^"]+)"|\'([^\']+)\'/s', $query, $qMatches)) {
                $quotes = array_values(array_filter(array_merge($qMatches[1], $qMatches[2])));
                if (count($quotes) >= 2) {
                    $excerptText = trim(end($quotes));
                } elseif (count($quotes) === 1) {
                    $q = trim($quotes[0]);
                    if (!preg_match('/^(?:chapter|ch)[-.\s]*\d+/i', $q)) {
                        $excerptText = $q;
                    }
                }
            }

            if (empty($excerptText)) {
                if (preg_match('/(?:\?|\:)\s*["\']?([^\r\n"\'?]{3,})["\']?$/s', trim($query), $tailMatch)) {
                    $excerptText = trim($tailMatch[1]);
                } elseif (preg_match('/\n\n["\']?(.+)["\']?$/s', trim($query), $tailMatch)) {
                    $excerptText = trim($tailMatch[1]);
                }
            }
        }

        return [
            'is_excerpt'     => $isExcerpt,
            'excerpt_text'   => $excerptText,
            'chapter_number' => $chapterNumber,
            'page_number'    => $pageNumber,
        ];
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

        if (preg_match('/(?:chapter|ch|boqonnaa|boqonna|kutaa|ምዕራፍ)[-.\s]*(\d+)/iu', $query, $matches)) {
            return (int) $matches[1];
        }

        foreach ($map as $word => $num) {
            if (preg_match('/\b(?:chapter|ch)[-.\s]*' . $word . '\b/i', $query) ||
                preg_match('/\b' . $word . '\s*(?:chapter|ch\.?)\b/i', $query)) {
                return $num;
            }
        }

        // Match known Smart Adama initiative / chapter topics
        $initiativeMap = [
            8  => ['smart social services', 'smart social service', 'social services', 'social service', 'smart health', 'smart education', 'smart utilities', 'smart utility'],
            7  => ['smart mobility', 'smart transportation'],
            6  => ['smart environment', 'organic production'],
            5  => ['smart urban design', 'land use management'],
            4  => ['smart security'],
            3  => ['digital adama'],
            2  => ['smart governance'],
            9  => ['smart tourism and culture', 'smart tourism'],
            10 => ['smart public relation', 'smart public relations', 'knowledge management'],
            11 => ['smart people'],
        ];

        foreach ($initiativeMap as $num => $phrases) {
            foreach ($phrases as $phrase) {
                if (str_contains($query, $phrase)) {
                    return $num;
                }
            }
        }

        return null;
    }

    public function isAffirmative(string $query): bool
    {
        $clean = rtrim(trim(mb_strtolower($query)), '?.!,;:');
        $affirmatives = [
            'yes', 'yeah', 'yep', 'yup', 'sure', 'ok', 'okay',
            'please', 'yes please', 'sure thing', 'go ahead', 'proceed',
            'continue', 'tell me', 'tell me more', 'let\'s do it', 'lets do it',
            'i would', 'i\'d like that', 'definitely', 'certainly', 'absolutely',
            'dive deeper', 'yes dive deeper', 'explore', 'yes explore', 'yes tell me'
        ];

        return in_array($clean, $affirmatives, true);
    }

    private function isFollowUp(string $query): bool
    {
        $clean = trim(mb_strtolower($query));
        $stripped = rtrim($clean, '?.!,;:');

        // Affirmative confirmation to an assistant prompt (e.g. "yes", "sure") is a follow-up
        if ($this->isAffirmative($query)) {
            return true;
        }

        // Pure isolated follow-up utterances with no standalone subject
        $pureFollowUps = [
            'why', 'why so', 'why is that', 'why not',
            'how', 'how so', 'how come', 'how does that work', 'how does it work',
            'tell me more', 'tell me more about it', 'tell me more about that',
            'explain that', 'explain it', 'explain this', 'explain more', 'explain further',
            'can you explain that', 'can you explain more',
            'give me an example', 'give an example', 'example please',
            'expand', 'expand on that', 'expand on this',
            'go deeper', 'elaborate', 'elaborate please', 'can you elaborate',
            'what about it', 'what about that', 'what about this',
            'what does that mean', 'what does it mean', 'what do you mean',
            'and then', 'and why', 'what else', 'more details please'
        ];

        if (in_array($stripped, $pureFollowUps, true)) {
            return true;
        }

        // Anaphoric references with demonstratives/pronouns (that, it, this, these, those, them)
        if (preg_match('/^(?:can you\s+)?(?:explain|tell me about|clarify|elaborate on)\s+(?:that|this|it|these|those|them)(?:\s+(?:further|more|deeply))?$/i', $stripped)) {
            return true;
        }

        if (preg_match('/^(?:why|how)\s+(?:is|does|did|would)\s+(?:that|it|this)\b/i', $stripped) && count(preg_split('/\s+/', $stripped)) <= 6) {
            return true;
        }

        if (preg_match('/^what about\s+(?:that|it|this|them|these|those)$/i', $stripped)) {
            return true;
        }

        if (preg_match('/^and\s+(?:why|how|what|then)\b/i', $stripped) && count(preg_split('/\s+/', $stripped)) <= 4) {
            return true;
        }

        return false;
    }
}
