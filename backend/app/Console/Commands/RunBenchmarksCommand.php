<?php

namespace App\Console\Commands;

use App\Services\RAG\RetrievalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunBenchmarksCommand extends Command
{
    protected $signature = 'rag:run-benchmarks';
    protected $description = 'Run an automated benchmark suite against the active RAG Hybrid Retrieval Engine.';

    private array $testQueries = [
        'Keyword' => 'mayor',
        'Semantic' => 'What are the responsibilities of the city administration?',
        'Phrase' => 'economic development and urban planning',
    ];

    public function handle(RetrievalService $retrievalService)
    {
        $this->info("========================================");
        $this->info("Smart Adama RAG - Automated Benchmark Suite");
        $this->info("========================================");

        $chunkCount = DB::table('content_chunks')->where('embedding_status', 'ready')->count();
        if ($chunkCount === 0) {
            $this->warn("No 'ready' content chunks found in the database. Benchmarks will return empty results.");
            $this->info("Please ingest a document first.");
        } else {
            $this->info("Active Corpus: {$chunkCount} embedded chunks.");
        }

        $this->line("");

        foreach ($this->testQueries as $type => $query) {
            $this->info("Running [{$type}] Benchmark: \"{$query}\"");
            
            $startTime = microtime(true);
            $results = $retrievalService->search($query, 5);
            $duration = microtime(true) - $startTime;

            if ($results->isEmpty()) {
                $this->error("  FAILED: No results returned.");
                $this->line("");
                continue;
            }

            $topResult = $results->first();

            $this->line("  Duration: " . number_format($duration, 3) . "s");
            
            $tableData = [
                [
                    'Rank' => 1,
                    'Page' => $topResult['page_number'] ?? 'N/A',
                    'RRF Score' => number_format($topResult['rrf_score'], 4),
                    'Semantic Rank' => $topResult['vector_rank'],
                    'Lexical Rank' => $topResult['keyword_rank'],
                    'Preview' => substr(str_replace("\n", ' ', $topResult['chunk_text']), 0, 50) . '...'
                ]
            ];

            $this->table(
                ['Rank', 'Page', 'RRF Score', 'Semantic Rank', 'Lexical Rank', 'Preview'],
                $tableData
            );
            
            $this->info("  SUCCESS: Retrieval pipeline operational.\n");
        }

        $this->info("========================================");
        $this->info("Benchmarks Complete!");
        $this->info("========================================");

        return 0;
    }
}
