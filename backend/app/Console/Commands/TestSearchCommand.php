<?php

namespace App\Console\Commands;

use App\Services\RAG\RetrievalService;
use Illuminate\Console\Command;

class TestSearchCommand extends Command
{
    protected $signature = 'rag:test-search {query} {--limit=5 : Number of results to return}';
    protected $description = 'Test the Hybrid Retrieval Engine (RRF) for a given query.';

    public function handle(RetrievalService $retrievalService)
    {
        $query = $this->argument('query');
        $limit = (int) $this->option('limit');

        $this->info("Executing Hybrid Search for: \"{$query}\"");
        $startTime = microtime(true);

        $results = $retrievalService->search($query, $limit);

        $duration = microtime(true) - $startTime;

        if ($results->isEmpty()) {
            $this->warn("No results found. (Duration: " . number_format($duration, 2) . "s)");
            return 0;
        }

        $this->info("Found " . $results->count() . " results in " . number_format($duration, 2) . "s\n");

        $tableData = [];
        foreach ($results as $index => $result) {
            $rank = $index + 1;
            $preview = substr(str_replace("\n", ' ', $result['chunk_text']), 0, 60) . '...';
            
            $tableData[] = [
                'Rank' => $rank,
                'RRF Score' => number_format($result['rrf_score'], 4),
                'Vector Score' => number_format($result['vector_score'], 4) . ' (#' . $result['vector_rank'] . ')',
                'Keyword Score' => number_format($result['keyword_score'], 4) . ' (#' . $result['keyword_rank'] . ')',
                'Page' => $result['page_number'] ?? 'N/A',
                'Preview' => $preview,
            ];
        }

        $this->table(
            ['Rank', 'RRF Score', 'Vector Score', 'Keyword Score', 'Page', 'Preview'],
            $tableData
        );

        return 0;
    }
}
