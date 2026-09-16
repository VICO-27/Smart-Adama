<?php

namespace App\Jobs;

use App\Models\AdminSetting;
use App\Models\Book;
use App\Models\BookPage;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\RAG\DocumentChunkingService;
use App\Services\RAG\DocumentStructureParser;
use App\Services\RAG\DTO\StructuredSection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class IngestBookChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 7200; // 2 hours max

    public bool $failOnTimeout = true;

    public string $bookId = '';

    public bool $resume = false;

    public function __construct(string $bookId, bool $resume = false)
    {
        $this->bookId = $bookId;
        $this->resume = $resume;
    }

    public function handle(DocumentStructureParser $parser, DocumentChunkingService $chunkingService, EmbeddingProviderInterface $embeddingProvider): void
    {
        $book = Book::find($this->bookId);

        if (! $book) {
            Log::error('IngestBookChunksJob: Book not found', ['book_id' => $this->bookId]);

            return;
        }

        $book->update(['status' => 'ingesting']);
        $book->logProgress('Starting semantic chunking and local vector embedding...', 'info');

        $pages = BookPage::where('book_id', $this->bookId)
            ->where('status', 'ready')
            ->orderBy('page_number')
            ->get();

        if ($pages->isEmpty()) {
            $book->update(['status' => 'failed']);
            $book->logProgress('Job failed: No pages available.', 'error');

            return;
        }

        try {
            // Idempotency: Clear previous chunks/sections/chapters for this book if retrying
            if (! $this->resume) {
                $book->logProgress('Clearing any partial ingestion data...', 'info');
                DB::table('content_chunks')->where('book_id', $book->id)->delete();
                $book->chapters()->each(function ($chapter) {
                    $chapter->sections()->delete();
                    $chapter->delete();
                });
            } else {
                $book->logProgress('Resuming ingestion, preserving existing chunks and structure...', 'info');
            }

            // 1. Structure Parsing
            $book->logProgress('Parsing document structure...', 'info');
            $structuredDocument = $parser->parse($pages);

            if (empty($structuredDocument->chapters)) {
                $book->logProgress('Warning: No chapters detected. Proceeding without structure.', 'warning');
            }

            // 2. Persist Structure & Chunking
            $book->logProgress('Persisting hierarchy and generating chunks...', 'info');

            $globalChunkIndex = 0;
            $allChunks = [];

            if (empty($structuredDocument->chapters)) {
                // Fallback: Treat the entire document as a single chapter/section
                $chapterModel = $book->chapters()->firstOrCreate(
                    ['title' => 'Main Document', 'order' => 1],
                    ['ingestion_status' => 'ready']
                );

                $sectionModel = $chapterModel->sections()->firstOrCreate(
                    ['title' => 'Full Content', 'order' => 1],
                    ['raw_text' => '']
                );

                $fullText = $pages->pluck('clean_text')->join("\n\n");

                $metadata = [
                    'book_id' => $book->id,
                    'chapter_id' => $chapterModel->id,
                    'section_id' => $sectionModel->id,
                    'chapter_title' => 'Main Document',
                    'section_title' => 'Full Content',
                    'start_page' => $pages->min('page_number') ?? 1,
                    'end_page' => $pages->max('page_number') ?? 1,
                    'source_order' => 1,
                    'structural_context' => [
                        'chapter' => 'Main Document',
                        'section' => 'Full Content',
                        'start_page' => $pages->min('page_number') ?? 1,
                        'end_page' => $pages->max('page_number') ?? 1,
                    ],
                ];

                $chunks = $chunkingService->chunk($fullText, $metadata);

                foreach ($chunks as $chunkData) {
                    $allChunks[] = [
                        'book_id' => $chunkData['book_id'],
                        'chapter_id' => $chunkData['chapter_id'],
                        'section_id' => $chunkData['section_id'],
                        'page_number' => $metadata['start_page'], // Default to start page for unstructured
                        'structural_context' => $chunkData['structural_context'],
                        'chunk_text' => $chunkData['chunk_text'],
                        'chunk_index' => $globalChunkIndex++,
                        'token_count' => $chunkData['token_count'],
                    ];
                }
            } else {
                foreach ($structuredDocument->chapters as $structChapter) {
                    $chapterModel = $book->chapters()->firstOrCreate(
                        ['title' => $structChapter->title, 'order' => $structChapter->order],
                        ['ingestion_status' => 'ready']
                    );

                    foreach ($structChapter->sections as $structSection) {
                        $sectionModel = $chapterModel->sections()->firstOrCreate(
                            ['title' => $structSection->title, 'order' => $structSection->order],
                            ['raw_text' => '']
                        );

                        $fullText = $structSection->getFullContent();
                        if (empty(trim($fullText))) {
                            continue;
                        }

                        // Prepare rich metadata for the chunker
                        $metadata = [
                            'book_id' => $book->id,
                            'chapter_id' => $chapterModel->id,
                            'section_id' => $sectionModel->id,
                            'chapter_title' => $chapterModel->title,
                            'section_title' => $sectionModel->title,
                            'start_page' => $structSection->startPage,
                            'end_page' => $structSection->endPage,
                            'source_order' => $structSection->order,
                            'structural_context' => [
                                'chapter' => $chapterModel->title,
                                'section' => $sectionModel->title,
                                'start_page' => $structSection->startPage,
                                'end_page' => $structSection->endPage,
                            ],
                        ];

                        // Generate chunks for this section
                        $chunks = $chunkingService->chunk($fullText, $metadata);

                        foreach ($chunks as $chunkData) {
                            // Find page range for this chunk
                            $chunkPage = $this->determineChunkPage($structSection, $chunkData['chunk_text']);

                            $allChunks[] = [
                                'book_id' => $chunkData['book_id'],
                                'chapter_id' => $chunkData['chapter_id'],
                                'section_id' => $chunkData['section_id'],
                                'page_number' => $chunkPage, // Using start page as primary page number
                                'structural_context' => $chunkData['structural_context'],
                                'chunk_text' => $chunkData['chunk_text'],
                                'chunk_index' => $globalChunkIndex++,
                                'token_count' => $chunkData['token_count'],
                            ];
                        }
                    }
                }
            }

            // Filter out existing chunks if resuming
            if ($this->resume) {
                $existingIndices = DB::table('content_chunks')->where('book_id', $book->id)->pluck('chunk_index')->toArray();
                if (! empty($existingIndices)) {
                    $allChunks = array_values(array_filter($allChunks, function ($c) use ($existingIndices) {
                        return ! in_array($c['chunk_index'], $existingIndices);
                    }));
                    $book->logProgress('Resuming: Skipping '.count($existingIndices).' already embedded chunks. '.count($allChunks).' chunks remaining.', 'info');
                }
            }

            if (empty($allChunks)) {
                $book->logProgress('No new chunks to embed. Document is fully processed.', 'success');

                return;
            }

            // 3. Batch Embedding Generation
            $book->logProgress('Generating embeddings for '.count($allChunks).' chunks via Ollama...', 'info');

            $batchSize = 20; // Batches for Ollama
            $chunkBatches = array_chunk($allChunks, $batchSize);

            $embeddedCount = 0;
            $failedCount = 0;

            foreach ($chunkBatches as $idx => $batch) {
                $texts = array_column($batch, 'chunk_text');
                $book->logProgress('Embedding batch '.($idx + 1).' of '.count($chunkBatches).'...', 'info');

                try {
                    $embeddings = $embeddingProvider->embedBatch($texts, 'document');
                } catch (\Exception $e) {
                    Log::error('Embedding batch failed', ['error' => $e->getMessage()]);
                    $failedCount += count($batch);

                    continue; // Skip this batch but keep going
                }

                foreach ($batch as $i => $chunkData) {
                    if (! isset($embeddings[$i])) {
                        $failedCount++;

                        continue;
                    }

                    $embeddingVector = $embeddings[$i];
                    if (count($embeddingVector) !== 1024) {
                        Log::warning('Dimension mismatch for chunk. Expected 1024, got '.count($embeddingVector));
                        $failedCount++;

                        continue;
                    }

                    $vectorStr = '['.implode(',', $embeddingVector).']';

                    $providerName = config('ai.embedding_provider', 'voyage');
                    $setting = AdminSetting::where('key', 'ai_embedding_provider')->first();
                    if ($setting) {
                        $providerName = $setting->value;
                    }
                    $modelName = config("ai.{$providerName}.embedding_model", $providerName === 'voyage' ? 'voyage-4' : 'qwen3-embedding:0.6b');

                    $chunkUuid = (string) Str::uuid();

                    DB::statement(
                        'INSERT INTO content_chunks
                        (id, book_id, section_id, page_number, structural_context, chunk_text, chunk_index, token_count, embedding_status, embedding, embedding_provider, embedding_model, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?::vector, ?, ?, ?, ?)',
                        [
                            $chunkUuid,
                            $chunkData['book_id'],
                            $chunkData['section_id'],
                            $chunkData['page_number'],
                            json_encode($chunkData['structural_context']),
                            $chunkData['chunk_text'],
                            $chunkData['chunk_index'],
                            $chunkData['token_count'],
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
                    $embeddedCount++;
                }
            }

            // 4. Verification & Activation
            if ($embeddedCount === 0) {
                throw new \Exception('Zero embeddings were successfully generated.');
            }

            $book->logProgress("Pipeline complete. Embedded $embeddedCount chunks. Failed: $failedCount.", $failedCount > 0 ? 'warning' : 'success');

            DB::transaction(function () use ($book) {
                // Archive previous published books
                Book::where('status', 'published')->where('id', '!=', $book->id)->update(['status' => 'archived']);
                // Publish new book
                $book->update(['status' => 'published']);
            });

            $book->logProgress('Document atomically activated!', 'success');

        } catch (Throwable $e) {
            Log::error('IngestBookChunksJob failed', [
                'book_id' => $this->bookId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $book->update(['status' => 'failed']);
            $book->logProgress('Job failed: '.$e->getMessage(), 'error');
            throw $e;
        }
    }

    private function determineChunkPage(StructuredSection $section, string $chunkText): int
    {
        // Simple heuristic to determine which page a chunk primarily belongs to
        // If we can't determine, fallback to section start page
        $firstWord = explode(' ', $chunkText)[0] ?? '';
        if (empty($firstWord)) {
            return $section->startPage;
        }

        foreach ($section->pageContents as $pageNum => $content) {
            if (stripos($content, $firstWord) !== false) {
                return $pageNum;
            }
        }

        return $section->startPage;
    }

    public function failed(Throwable $e): void
    {
        $book = Book::find($this->bookId);
        if ($book) {
            $book->update(['status' => 'failed']);
            $book->logProgress('Pipeline fatally failed: '.$e->getMessage(), 'error');
        }
    }
}
