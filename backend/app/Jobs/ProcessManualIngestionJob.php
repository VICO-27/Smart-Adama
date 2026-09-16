<?php

namespace App\Jobs;

use App\Models\AdminSetting;
use App\Models\Book;
use App\Models\IngestionJob;
use App\Models\Section;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\RAG\ChunkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessManualIngestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public $tries = 3;

    public function __construct(
        public string $jobId,
        public string $mode // 'structured' or 'page'
    ) {}

    public function handle(ChunkingService $chunkService, EmbeddingProviderInterface $embeddingProvider): void
    {
        $job = IngestionJob::find($this->jobId);
        if (! $job) {
            return;
        }

        $book = Book::find($job->book_id);
        if (! $book) {
            $this->failJob($job, 'Book not found.');

            return;
        }

        try {
            $this->logTerminal('Initialization', "Starting manual ingestion job [ID: {$job->id}] in '{$this->mode}' mode.");
            $job->update(['status' => 'processing', 'current_stage' => 'validation']);

            // 1. Validation & Chunking Preparation
            $this->logTerminal('Validation', "Validating book content for UUID: {$book->id}");
            $chunksData = [];

            if ($this->mode === 'structured') {
                $sections = Section::whereHas('chapter', fn ($q) => $q->where('book_id', $book->id))
                    ->orderBy('order')->get();

                if ($sections->isEmpty()) {
                    throw new \Exception('No sections found for structured mode.');
                }

                foreach ($sections as $section) {
                    if (empty(trim($section->raw_text))) {
                        continue;
                    }

                    $sectionChunks = $chunkService->chunk($section->raw_text, $section->id);
                    foreach ($sectionChunks as $c) {
                        $chunksData[] = array_merge($c, ['section_id' => $section->id, 'page_number' => 1]);
                    }
                }
            } else {
                $pages = $book->pages()->orderBy('page_number')->get();
                if ($pages->isEmpty()) {
                    throw new \Exception('No pages found for page mode.');
                }

                // Fallback chapter/section for page mode chunks
                $chapter = $book->chapters()->firstOrCreate(['title' => 'Main Document', 'order' => 1]);
                $section = $chapter->sections()->firstOrCreate(['title' => 'Pages', 'order' => 1]);

                foreach ($pages as $page) {
                    if (empty(trim($page->clean_text))) {
                        continue;
                    }

                    $pageChunks = $chunkService->chunk($page->clean_text, $section->id);
                    foreach ($pageChunks as $c) {
                        $chunksData[] = array_merge($c, ['section_id' => $section->id, 'page_number' => $page->page_number]);
                    }
                }
            }

            if (empty($chunksData)) {
                throw new \Exception('No valid content found to chunk.');
            }

            $totalChunks = count($chunksData);
            $this->logTerminal('Chunking', "Chunking completed. Generated {$totalChunks} chunks.");

            $job->update([
                'current_stage' => 'chunking',
                'total_items' => count($chunksData),
                'completed_items' => 0,
            ]);

            // Smart Sync: Only embed new chunks and reuse existing vectors
            $existingChunks = DB::table('content_chunks')
                ->where('book_id', $book->id)
                ->get(['id', 'chunk_text']);

            $existingMap = [];
            foreach ($existingChunks as $chunk) {
                $hash = md5($chunk->chunk_text);
                if (! isset($existingMap[$hash])) {
                    $existingMap[$hash] = [];
                }
                $existingMap[$hash][] = $chunk->id;
            }

            $chunksToEmbed = [];
            $reusedCount = 0;
            $keptIds = [];

            foreach ($chunksData as $index => $chunkData) {
                $hash = md5($chunkData['chunk_text']);
                if (isset($existingMap[$hash]) && count($existingMap[$hash]) > 0) {
                    $existingId = array_shift($existingMap[$hash]);
                    $keptIds[] = $existingId;

                    DB::table('content_chunks')->where('id', $existingId)->update([
                        'section_id' => $chunkData['section_id'],
                        'page_number' => $chunkData['page_number'],
                        'structural_context' => json_encode($chunkData['structural_context'] ?? []),
                        'chunk_index' => $index,
                        'token_count' => $chunkData['token_count'] ?? 0,
                        'updated_at' => now(),
                    ]);
                    $reusedCount++;
                } else {
                    $chunkData['chunk_index'] = $index;
                    $chunksToEmbed[] = $chunkData;
                }
            }

            // Cleanup orphaned chunks (not kept)
            $deleted = 0;
            if (count($keptIds) > 0) {
                $deleted = DB::table('content_chunks')->where('book_id', $book->id)->whereNotIn('id', $keptIds)->delete();
            } else {
                $deleted = DB::table('content_chunks')->where('book_id', $book->id)->delete();
            }

            $this->logTerminal('Cleanup', "Smart sync: Reused {$reusedCount} existing chunks. Cleared {$deleted} orphaned chunks.");

            $chunksData = $chunksToEmbed;
            $totalChunks = count($chunksData);

            // 2. Embedding Generation & Vector Storage
            $job->update(['current_stage' => 'embedding']);

            $batchSize = 2;
            $batches = array_chunk($chunksData, $batchSize);
            $totalBatches = count($batches);

            if ($totalChunks > 0) {
                $this->logTerminal('Embedding', "Starting vector generation for {$totalChunks} new chunks in {$totalBatches} batches (size: {$batchSize}).");
            } else {
                $this->logTerminal('Embedding', 'No new chunks require embedding.');
            }

            $completed = 0;
            $failed = 0;

            foreach ($batches as $batch) {
                if ($job->fresh()->status === 'cancelled') {
                    throw new \Exception('Job cancelled by admin.');
                }

                $texts = array_column($batch, 'chunk_text');

                try {
                    $embeddings = $embeddingProvider->embedBatch($texts, 'document');

                    $providerName = config('ai.embedding_provider', 'voyage');
                    $setting = AdminSetting::where('key', 'ai_embedding_provider')->first();
                    if ($setting) {
                        $providerName = $setting->value;
                    }
                    $modelName = config("ai.{$providerName}.embedding_model", $providerName === 'voyage' ? 'voyage-4' : 'qwen3-embedding:0.6b');

                    foreach ($batch as $i => $chunkData) {
                        if (isset($embeddings[$i]) && count($embeddings[$i]) === 1024) {
                            $vectorStr = '['.implode(',', $embeddings[$i]).']';
                            $chunkUuid = (string) Str::uuid();

                            DB::statement(
                                'INSERT INTO content_chunks
                                (id, book_id, section_id, page_number, structural_context, chunk_text, chunk_index, token_count, embedding_status, embedding, embedding_provider, embedding_model, created_at, updated_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?::vector, ?, ?, ?, ?)',
                                [
                                    $chunkUuid,
                                    $book->id,
                                    $chunkData['section_id'],
                                    $chunkData['page_number'],
                                    json_encode($chunkData['structural_context'] ?? []),
                                    $chunkData['chunk_text'],
                                    $chunkData['chunk_index'],
                                    $chunkData['token_count'] ?? 0,
                                    'ready',
                                    $vectorStr,
                                    $providerName,
                                    $modelName,
                                    now(),
                                    now(),
                                ]
                            );

                            if ($providerName === 'voyage') {
                                DB::statement(
                                    'INSERT INTO content_chunk_embeddings (id, chunk_id, provider, model, dimension, embedding, created_at, updated_at)
                                     VALUES (?, ?, ?, ?, ?, ?::vector, ?, ?)
                                     ON CONFLICT (chunk_id, provider, model) DO UPDATE SET embedding = EXCLUDED.embedding, dimension = EXCLUDED.dimension, updated_at = EXCLUDED.updated_at',
                                    [
                                        (string) Str::uuid(),
                                        $chunkUuid,
                                        'voyage',
                                        $modelName,
                                        1024,
                                        $vectorStr,
                                        now(),
                                        now(),
                                    ]
                                );
                            }

                            $completed++;
                        } else {
                            $failed++;
                        }
                    }
                } catch (\Exception $e) {
                    $failed += count($batch);
                    Log::error('Embedding batch failed', ['error' => $e->getMessage()]);
                    $this->logTerminal('Error', 'Embedding batch failed: '.$e->getMessage());
                }

                $this->logTerminal('Embedding', "Processed batch. Success: {$completed}, Failed: {$failed}.");
                $job->update(['completed_items' => $completed, 'failed_items' => $failed]);
            }

            // 3. Completion
            $job->update([
                'current_stage' => 'indexing',
            ]);

            DB::transaction(function () use ($book, $job) {
                // Archive older versions with same title
                Book::where('title', $book->title)->where('status', 'published')->where('id', '!=', $book->id)->update(['status' => 'archived']);
                $book->update(['status' => 'published']);

                $job->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            });

            $this->logTerminal('Indexing', 'Database transaction committed successfully.');
            $this->logTerminal('Success', "Ingestion job completed! Reused: {$reusedCount} | Embedded: {$completed}");

        } catch (\Exception $e) {
            $this->failJob($job, $e->getMessage());
        }
    }

    private function failJob(IngestionJob $job, string $error): void
    {
        $job->update([
            'status' => 'failed',
            'failed_at' => now(),
            'last_error' => $error,
        ]);
        Log::error('Manual Ingestion Job Failed: '.$error);
        $this->logTerminal('Failed', 'Job aborted with error: '.$error);
    }

    private function logTerminal(string $stage, string $message): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] [{$stage}] {$message}".PHP_EOL;

        $path = storage_path('logs/jobs');
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents("{$path}/{$this->jobId}.log", $logLine, FILE_APPEND);
    }
}
