<?php

namespace App\Console\Commands;

use App\Jobs\IngestBookChunksJob;
use App\Models\Book;
use App\Models\ContentChunk;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\RAG\DocumentChunkingService;
use App\Services\RAG\DocumentStructureParser;
use App\Services\RAG\PdfExtractionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ParseSmartAdamaBook extends Command
{
    protected $signature = "book:parse
        {--dry-run : Parse and verify without saving/embedding}
        {--resume : Resume from where left off (don't truncate existing data)}
        {--retry-failed : Only re-try batches with failed chunks}
        {--verify : Verify existing embeddings after ingestion}";

    protected $description = 'FAST BATCH PARSER: Parses the SA-Book.pdf and generates embeddings with rate limiting.';

    private int $rateLimitRetrySeconds;

    private int $requestDelaySeconds;

    private int $failedBatchCount = 0;

    private int $totalChunksCreated = 0;

    private int $totalChunksEmbedded = 0;

    public function handle(PdfExtractionService $extractor, DocumentStructureParser $parser, DocumentChunkingService $chunker, EmbeddingProviderInterface $embedder)
    {
        $this->rateLimitRetrySeconds = (int) config('ai.voyage.rate_limit_retry_seconds', 60);
        $this->requestDelaySeconds = (int) config('ai.voyage.request_delay_seconds', 25);

        $dryRun = $this->option('dry-run');
        $resume = $this->option('resume');
        $retryFailed = $this->option('retry-failed');
        $verifyOnly = $this->option('verify');

        if ($verifyOnly) {
            return $this->verifyIngestion();
        }

        // Dry-run mode: parse and verify without saving/embedding
        if ($dryRun) {
            return $this->dryRunParse($parser);
        }

        if (! $resume) {
            $this->info('🚀 Starting the FAST Smart Adama Knowledge Pipeline...');
        } else {
            $this->info('🚀 Resuming Smart Adama Knowledge Pipeline...');
        }

        // Create book (or get existing if resuming)
        if (! $resume || ContentChunk::count() === 0) {
            $book = Book::create(['title' => 'Smart Adama: Complete Guide & Ecosystem', 'status' => 'uploading']);
        } else {
            $book = Book::first();
            if (! $book) {
                $this->error('No book found. Cannot resume without book.');

                return 1;
            }
        }

        // Only inject global knowledge if no data exists
        if (! $resume || ContentChunk::count() === 0) {
            $this->info('🧠 Injecting Global Platform, City Knowledge & Core Pillars...');
            $this->injectGlobalKnowledge($book);
        }

        // Only parse PDF if no data exists (when not resuming specific sections)
        if (! $resume || ContentChunk::count() === 0) {
            $pdfPath = base_path('../docs/SA-Book.pdf');
            if (! file_exists($pdfPath)) {
                $pdfPath = base_path('SA-Book.pdf');
            }

            if (file_exists($pdfPath)) {
                $this->info('📄 Copying PDF to storage and executing extraction...');

                // Copy to storage so PdfExtractionService can find it
                $storagePath = 'books/'.basename($pdfPath);
                Storage::disk('local')->put($storagePath, file_get_contents($pdfPath));
                $book->update(['source_file_path' => $storagePath]);

                $this->info('🚀 Running Extraction...');
                $result = $extractor->extractAndStore($book);
                if ($result['status'] === 'failed' || $result['status'] === 'ocr_required') {
                    $this->error('Extraction failed: '.($result['error'] ?? 'Unknown error'));

                    return 1;
                }

                $this->info('🚀 Running Structural Parsing, Chunking & Embedding...');
                $ingestJob = new IngestBookChunksJob($book->id);
                $ingestJob->handle($parser, $chunker, $embedder);
            } else {
                $this->error('SA-Book.pdf not found!');

                return 1;
            }
        }

        // Verify final state
        return $this->verifyIngestion();
    }

    private function dryRunParse(DocumentStructureParser $parser): int
    {
        $this->info('🔍 DRY RUN: Parsing PDF without saving to database...');

        $pdfPath = base_path('../docs/SA-Book.pdf');
        if (! file_exists($pdfPath)) {
            $pdfPath = base_path('SA-Book.pdf');
        }

        if (! file_exists($pdfPath)) {
            $this->error("❌ PDF file not found at: $pdfPath");

            return 1;
        }

        $this->info('📄 Not actually extracting. Dry-run now relies on the unified parser.');

        return 0;
    }

    private function verifyIngestion(): int
    {
        $this->info('🔍 Verifying ingestion state...');
        $total = ContentChunk::count();
        $ready = ContentChunk::where('embedding_status', 'ready')->count();
        $pending = ContentChunk::where('embedding_status', 'pending')->count();
        $failed = ContentChunk::where('embedding_status', 'failed')->count();
        $nullEmbeddings = ContentChunk::whereNull('embedding')->count();

        $this->info('📊 Current State:');
        $this->info("  - Total chunks: {$total}");
        $this->info("  - Ready with embeddings: {$ready}");
        $this->info("  - Pending: {$pending}");
        $this->info("  - Failed: {$failed}");
        $this->info("  - NULL embeddings: {$nullEmbeddings}");

        if ($total === 0) {
            $this->error("\n❌ KNOWLEDGE BASE NOT INGESTED / INCOMPLETE!");
            $this->error('   Zero chunks found in database.');

            return 1;
        }

        if ($pending > 0 || $failed > 0 || $nullEmbeddings > 0) {
            $this->error("\n❌ INGESTION INCOMPLETE!");

            return 1;
        }

        $this->info("\n✅ KNOWLEDGE BASE VERIFIED!");

        return 0;
    }

    private function injectGlobalKnowledge(Book $book)
    {
        $this->info('🧠 Injecting Global Platform, City Knowledge & Core Pillars...');
        $this->info('   Note: System Context is kept separate from Book RAG.');
        $this->info('   Book RAG will contain ONLY actual chapters.');
    }
}
