<?php

namespace App\Services\RAG;

use App\Models\ContentChunk;
use App\Models\Section;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Writes chunked + embedded content_chunks for a single section.
 * Called by GenerateChunkEmbeddingJob after ChunkingService produces the chunks.
 * Re-ingestion deletes stale chunks transactionally before inserting new ones (Req 4.5).
 */
class IngestionService
{
    public function __construct(
        private readonly ChunkingService        $chunker,
        private readonly EmbeddingProviderInterface $embedder,
    ) {
    }

    /**
     * Ingest all chunks for a section:
     *  1. Delete existing chunks (idempotent re-ingestion, Req 4.5).
     *  2. Chunk the raw text.
     *  3. Batch-embed all chunks in one API call.
     *  4. Persist ContentChunk rows with the vector via raw SQL (pgvector).
     *  5. Sleep to respect API rate limits.
     *
     * @throws \App\Exceptions\AiProviderException
     */
    public function ingestSection(Section $section): void
    {
        $rawText = $section->raw_text ?? '';

        if (empty(trim($rawText))) {
            Log::info('IngestionService: section has no text, skipping', [
                'section_id' => $section->id,
            ]);
            return;
        }

        $chunks = $this->chunker->chunk($rawText, $section->id);

        if (empty($chunks)) {
            return;
        }

        // Batch embed all chunk texts in one API call with 'document' input_type
        $texts      = array_column($chunks, 'chunk_text');
        $embeddings = $this->embedder->embedBatch($texts, 'document');

        DB::transaction(function () use ($section, $chunks, $embeddings) {
            // Delete stale chunks for this section (Req 4.5)
            ContentChunk::where('section_id', $section->id)->delete();

            foreach ($chunks as $i => $chunk) {
                $vector = $embeddings[$i] ?? null;

                if ($vector === null) {
                    Log::warning('IngestionService: missing embedding for chunk', [
                        'section_id'  => $section->id,
                        'chunk_index' => $chunk['chunk_index'],
                    ]);
                    continue;
                }

                $activeProvider = \App\Models\AdminSetting::where('key', 'ai_embedding_provider')->first()?->value ?: config('ai.embedding_provider', 'voyage');
                $activeModel = config("ai.{$activeProvider}.embedding_model", $activeProvider === 'voyage' ? 'voyage-4' : 'qwen3-embedding:0.6b');

                // Insert the chunk first via Eloquent (no vector yet)
                $contentChunk = ContentChunk::create([
                    'section_id'         => $section->id,
                    'chunk_text'         => $chunk['chunk_text'],
                    'chunk_index'        => $chunk['chunk_index'],
                    'token_count'        => $chunk['token_count'],
                    'embedding_status'   => 'ready',
                    'embedding_provider' => $activeProvider,
                    'embedding_model'    => $activeModel,
                ]);

                // Update the pgvector column via raw SQL
                $vectorStr = '[' . implode(',', $vector) . ']';

                DB::statement(
                    'UPDATE content_chunks SET embedding = ? WHERE id = ?',
                    [$vectorStr, $contentChunk->id]
                );

                if ($activeProvider === 'voyage') {
                    DB::statement(
                        "INSERT INTO content_chunk_embeddings (id, chunk_id, provider, model, dimension, embedding, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?::vector, ?, ?)
                         ON CONFLICT (chunk_id, provider, model) DO UPDATE SET embedding = EXCLUDED.embedding, dimension = EXCLUDED.dimension, updated_at = EXCLUDED.updated_at",
                        [
                            (string) \Illuminate\Support\Str::uuid(),
                            $contentChunk->id,
                            'voyage',
                            $activeModel,
                            1024,
                            $vectorStr,
                            now(),
                            now()
                        ]
                    );
                }
            }
        });

        Log::info('IngestionService: section ingested, pausing to respect rate limits', [
            'section_id'  => $section->id,
            'chunk_count' => count($chunks),
        ]);

        // Rate-limit safety pause (from config)
        sleep((int) config('ai.ingestion_sleep_seconds', 2));
    }
}