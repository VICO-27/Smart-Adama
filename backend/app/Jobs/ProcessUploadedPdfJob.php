<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\RAG\PdfExtractionService;
use App\Jobs\IngestBookChunksJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUploadedPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 1;

    public function __construct(public Book $book)
    {
    }

    public function handle(PdfExtractionService $extractor)
    {
        $startTime = microtime(true);

        try {
            $this->book->update(['status' => 'extracting']);
            $this->book->logProgress('Starting PDF extraction pipeline...', 'info');
            
            // 1. Parse PDF, normalize, and store pages
            $result = $extractor->extractAndStore($this->book);
            
            $duration = microtime(true) - $startTime;

            if ($result['status'] === 'failed' || $result['status'] === 'ocr_required') {
                $this->book->update([
                    'status' => $result['status'],
                    'processing_metadata' => array_merge($this->book->processing_metadata ?? [], [
                        'error' => $result['error'] ?? 'Unknown error',
                        'duration_seconds' => round($duration, 2),
                    ])
                ]);
                return;
            }
            
            // 2. Mark ready for Phase 3 (RAG chunking)
            $this->book->update([
                'status' => 'ready_for_chunking',
                'processing_metadata' => array_merge($this->book->processing_metadata ?? [], [
                    'pages' => $result['pages'],
                    'original_chars' => $result['original_chars'],
                    'cleaned_chars' => $result['cleaned_chars'],
                    'suspicious_pages' => $result['suspicious_pages'],
                    'duration_seconds' => round($duration, 2),
                ])
            ]);
            
            // 3. Automatically hand off to the Phase 3 Chunking/Embedding pipeline
            $this->book->logProgress('Dispatching chunk ingestion job...', 'info');
            IngestBookChunksJob::dispatch($this->book->id);
            
        } catch (\Exception $e) {
            $this->book->update([
                'status' => 'failed',
                'processing_metadata' => array_merge($this->book->processing_metadata ?? [], [
                    'error' => $e->getMessage(),
                ])
            ]);
            Log::error("ProcessUploadedPdfJob failed", [
                'book_id' => $this->book->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
