<?php

namespace App\Console\Commands;

use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Services\RAG\PromptBuilderService;
use App\Services\RAG\RetrievalService;
use Illuminate\Console\Command;

class TestAskCommand extends Command
{
    protected $signature = 'rag:test-ask {query} {--limit=5 : Number of results to retrieve}';
    protected $description = 'Test the end-to-end Grounded RAG generation pipeline for a query.';

    public function handle(
        RetrievalService $retrievalService,
        PromptBuilderService $promptBuilder,
        LLMGatewayInterface $llm
    ) {
        $query = $this->argument('query');
        $limit = (int) $this->option('limit');

        $this->info("========================================");
        $this->info("Question: \"{$query}\"");
        $this->info("========================================");

        $startTime = microtime(true);
        $this->info("[1/4] Searching the knowledge base...");

        $chunks = $retrievalService->search($query, $limit);
        $grounded = $chunks->isNotEmpty();

        if (!$grounded) {
            $this->warn("No relevant context found. Proceeding with ungrounded prompt...");
        } else {
            $this->info("Found " . $chunks->count() . " relevant chunks.");
            foreach ($chunks as $idx => $chunk) {
                $this->line("  → [Rank " . ($idx + 1) . "] Page: " . ($chunk['page_number'] ?? 'N/A') . " | RRF Score: " . number_format($chunk['rrf_score'], 4));
            }
        }

        $this->info("\n[2/4] Building grounded prompt...");
        $messages = $promptBuilder->buildMessages(
            [], // Empty history for CLI test
            $chunks,
            $query,
            ['isGrounded' => $grounded]
        );

        $this->info("\n[3/4] Generating response via configured LLM...");
        $this->line("----------------------------------------");

        $responseContent = '';

        try {
            foreach ($llm->streamChat($messages) as $token) {
                echo $token;
                $responseContent .= $token;
            }
        } catch (\Throwable $e) {
            $this->error("\nLLM Generation failed: " . $e->getMessage());
            return 1;
        }

        $this->line("\n----------------------------------------");

        $duration = microtime(true) - $startTime;
        $this->info("\n[4/4] Complete! Total time: " . number_format($duration, 2) . "s");

        if ($grounded) {
            $this->info("\nCitations Available:");
            foreach ($chunks as $chunk) {
                $pageNum = $chunk['page_number'] ?? 'N/A';
                $heading = $chunk['structural_context']['heading'] ?? 'General Context';
                $this->line(" - Page {$pageNum} ({$heading}) [RRF: " . number_format($chunk['rrf_score'], 4) . "]");
            }
        }

        return 0;
    }
}
