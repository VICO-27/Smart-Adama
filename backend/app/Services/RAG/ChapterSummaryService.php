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
        if ($chunks->count() <= 12) {
            return $chunks;
        }

        $batches = $chunks->chunk(8);
        $compressedChunks = collect([]);
        
        foreach ($batches as $batch) {
            $messages = $this->promptBuilder->buildMessages(
                [], 
                $batch, 
                'Summarize the core concepts in this section of the text concisely. Do not invent facts.', 
                ['isGrounded' => true], 
                false, 
                'SUMMARY'
            );
            
            $summaryText = $this->llm->chat($messages);
            
            $firstChunk = $batch->first();
            $compressedChunks->push([
                'id' => $firstChunk['id'],
                'chunk_text' => $summaryText,
                'page_number' => $firstChunk['page_number'],
                'structural_context' => $firstChunk['structural_context'],
                'rrf_score' => 1.0,
                'keyword_score' => 1.0,
                'vector_score' => 1.0,
            ]);
        }
        
        if ($compressedChunks->count() > 1) {
            $synthesisPrompt = "Synthesize these summaries into one cohesive chapter summary, ensuring the most important points from ALL sections are preserved. Do not omit major sections. Do not invent facts.";
            $messages = $this->promptBuilder->buildMessages([], $compressedChunks, $synthesisPrompt, ['isGrounded' => true], false, 'SUMMARY');
            $finalSummaryText = $this->llm->chat($messages);
            
            $firstChunk = $compressedChunks->first();
            return collect([[
                'id' => $firstChunk['id'],
                'chunk_text' => $finalSummaryText,
                'page_number' => $firstChunk['page_number'],
                'structural_context' => $firstChunk['structural_context'],
                'rrf_score' => 1.0,
                'keyword_score' => 1.0,
                'vector_score' => 1.0,
            ]]);
        }
        
        return $compressedChunks;
    }
}
