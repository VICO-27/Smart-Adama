<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateVoyageEmbeddings extends Command
{
    protected $signature = 'rag:migrate-voyage {--limit= : Number of chunks to process}';
    protected $description = 'Migrates content chunks to Voyage embeddings using content_chunk_embeddings table';

    public function handle(\App\Services\AI\EmbeddingProviderManager $manager)
    {
        $provider = 'voyage';
        $model = config('ai.voyage.embedding_model', env('VOYAGE_MODEL', 'voyage-4'));
        $dimension = config('ai.voyage.embedding_dimension', env('VOYAGE_EMBEDDING_DIMENSION', 1024));

        $embedder = new \App\Services\AI\VoyageEmbeddingProvider();

        // Fetch chunks that do NOT have a voyage record for this model
        $query = \Illuminate\Support\Facades\DB::table('content_chunks')
            ->select('id', 'chunk_text')
            ->whereNotIn('id', function($q) use ($provider, $model) {
                $q->select('chunk_id')
                  ->from('content_chunk_embeddings')
                  ->where('provider', $provider)
                  ->where('model', $model);
            });

        if ($this->option('limit')) {
            $query->limit((int) $this->option('limit'));
        }

        $chunks = $query->get();

        if ($chunks->isEmpty()) {
            $this->info("No chunks require migration. All done.");
            return 0;
        }

        $this->info("Found {$chunks->count()} chunks to migrate to {$provider} ({$model}).");

        $batches = $chunks->chunk(3);
        $totalEmbedded = 0;

        foreach ($batches as $batch) {
            $texts = $batch->pluck('chunk_text')->toArray();
            $ids = $batch->pluck('id')->toArray();

            $this->info("Embedding batch of " . count($texts) . "...");
            try {
                $vectors = $embedder->embedBatch($texts, 'document');

                foreach ($vectors as $i => $vector) {
                    $vectorStr = '[' . implode(',', $vector) . ']';

                    \Illuminate\Support\Facades\DB::statement("
                        INSERT INTO content_chunk_embeddings
                        (id, chunk_id, provider, model, dimension, embedding, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?::vector, ?, ?)
                        ON CONFLICT (chunk_id, provider, model) DO NOTHING
                    ", [
                        \Illuminate\Support\Str::uuid(),
                        $ids[$i],
                        $provider,
                        $model,
                        $dimension,
                        $vectorStr,
                        now(),
                        now(),
                    ]);
                }

                $totalEmbedded += count($vectors);
            } catch (\Exception $e) {
                $this->error("Batch failed: " . $e->getMessage());
                // Sleep for 65 seconds on failure (likely 429 rate limit or TPM exceeded)
                sleep(65);
                continue;
            }

            // Free tier Voyage rate limit is 10K TPM. 3 chunks = ~7500 tokens.
            $this->info("Sleeping 61 seconds to reset TPM limit...");
            sleep(61);
        }

        $this->info("Migration complete. Successfully embedded {$totalEmbedded} chunks.");
        return 0;
    }
}
