<?php

namespace App\Services\RAG;

use Illuminate\Support\Collection;

class GroundingDecisionService
{
    /**
     * Determine the evidence quality and whether to proceed with a grounded answer.
     * Thresholds can be configured in config/ai.php.
     */
    public function evaluate(Collection $chunks, array $understanding, string $responseMode = 'NORMAL'): array
    {
        if ($chunks->isEmpty()) {
            return [
                'isGrounded' => false,
                'confidence' => 'NONE',
                'evidenceQuality' => 'Empty retrieval',
                'selectedSources' => [],
            ];
        }

        $semanticThreshold = config('ai.rag.semantic_threshold', 0.65);
        $lexicalThreshold = config('ai.rag.lexical_threshold', 0.1);
        $rrfThreshold = config('ai.rag.rrf_threshold', 0.01);

        $selectedSources = [];
        $strongCount = 0;
        $moderateCount = 0;
        $weakCount = 0;

        foreach ($chunks as $chunk) {
            $isStrongLexical = $chunk['keyword_score'] >= $lexicalThreshold;
            $isStrongSemantic = $chunk['vector_score'] >= $semanticThreshold;
            $isStrongRrf = $chunk['rrf_score'] >= $rrfThreshold;

            // If it's an excerpt or short entity lookup, lexical match is highly trusted
            if ((! empty($understanding['is_excerpt']) || $understanding['is_entity_lookup']) && ($isStrongLexical || $chunk['keyword_score'] > 0)) {
                $selectedSources[] = $chunk;
                $strongCount++;

                continue;
            }

            if ($isStrongSemantic || $isStrongRrf) {
                $selectedSources[] = $chunk;
                $strongCount++;
            } elseif ($chunk['vector_score'] >= ($semanticThreshold - 0.1) || $chunk['keyword_score'] > 0) {
                // Keep moderate chunks but don't count them as strong evidence
                $selectedSources[] = $chunk;
                $moderateCount++;
            } else {
                $weakCount++;
            }
        }

        // Limit context dynamically based on response mode
        $maxChunks = match ($responseMode) {
            'SHORT' => 2,
            'NORMAL' => 4,
            'CHAPTER_QUIZ', 'QUIZ' => 5,
            'EXCERPT_EXPLANATION', 'DETAILED', 'DEEP', 'STEP_BY_STEP', 'COMPARISON' => 4,
            default => 4,
        };

        $selectedSources = array_slice($selectedSources, 0, $maxChunks);

        if ($strongCount > 0) {
            $confidence = 'STRONG';
            $isGrounded = true;
        } elseif ($moderateCount > 0) {
            $confidence = 'MODERATE';
            $isGrounded = true;
        } else {
            $confidence = 'NONE'; // Weak evidence is discarded
            $isGrounded = false;
            $selectedSources = [];
        }

        return [
            'isGrounded' => $isGrounded,
            'confidence' => $confidence,
            'evidenceQuality' => "Strong: $strongCount, Moderate: $moderateCount, Weak: $weakCount",
            'selectedSources' => collect($selectedSources),
        ];
    }
}
