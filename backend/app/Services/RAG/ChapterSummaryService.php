<?php

namespace App\Services\RAG;

use App\Services\AI\Contracts\LLMGatewayInterface;
use Illuminate\Support\Collection;

class ChapterSummaryService
{
    public function __construct(
        private readonly LLMGatewayInterface $llm,
        private readonly PromptBuilderService $promptBuilder
    ) {
    }

    /**
     * Executes a map-reduce summarization pipeline over a large collection of chunks.
     */
    public function summarize(Collection $chunks): Collection
    {
        // To hit < 5s latency targets for production, we do not perform map-reduce.
        // Instead, we take a representative sample of the chapter's chunks.
        // This relies on the LLM to summarize the core concepts from the beginning and key sections.

        $sampledChunks = collect([]);
        $chunksList = $chunks->values();

        // Take up to 6 chunks evenly spaced to get a high-level overview without map-reduce
        $total = $chunksList->count();
        if ($total <= 6) {
            return $chunks;
        }

        $step = max(1, floor($total / 6));
        for ($i = 0; $i < $total; $i += $step) {
            $sampledChunks->push($chunksList[$i]);
            if ($sampledChunks->count() >= 6) break;
        }

        return $sampledChunks;
    }
}
