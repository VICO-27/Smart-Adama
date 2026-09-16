<?php

namespace App\Console\Commands;

use App\Services\RAG\RetrievalService;
use Illuminate\Console\Command;

class CalibrateRetrievalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:calibrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run calibration queries to determine optimal RAG thresholds';

    /**
     * Execute the console command.
     */
    public function handle(RetrievalService $retrievalService)
    {
        $queries = [
            'mayor',
            'Ashenafi Deresa',
            'who is Ashenafi Deresa',
            'kuku sebsibe',
            'Ashu',
            'Ashenafi',
            'who is hailu',
            'tell me about the bg of adama',
            'hmm how is the mayor',
            'hi there',
            'adma',
            'abt',
            'Adama population',
            'The BG of Adama is located in central Ethiopia',
            'Who is the mayor of Adama City?',
        ];

        $this->info('Starting RAG Retrieval Calibration...');

        $results = [];

        foreach ($queries as $query) {
            $this->warn("\nQuery: '{$query}'");

            // Bypass limit to see more candidates
            $chunks = $retrievalService->search($query, $query, 10);

            if ($chunks->isEmpty()) {
                $this->error('  No candidates retrieved.');

                continue;
            }

            foreach ($chunks->take(5) as $idx => $chunk) {
                $rank = $idx + 1;
                $rrf = $chunk['rrf_score'] ?? 0;
                $vec = $chunk['vector_score'] ?? 0;
                $lex = $chunk['keyword_score'] ?? 0;
                $text = mb_substr(str_replace("\n", ' ', $chunk['chunk_text']), 0, 60).'...';

                $this->line(sprintf('  [%d] RRF: %.4f | Vec: %.4f | Lex: %.4f | %s', $rank, $rrf, $vec, $lex, $text));
            }
        }

        $this->info("\nCalibration complete.");
    }
}
