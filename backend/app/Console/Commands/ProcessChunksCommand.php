<?php

namespace App\Console\Commands;

use App\Jobs\IngestBookChunksJob;
use App\Models\Book;
use Illuminate\Console\Command;

class ProcessChunksCommand extends Command
{
    protected $signature = 'rag:process-chunks {book_id} {--resume : Resume previous failed ingestion without deleting successful chunks}';
    protected $description = 'Process chunking and embedding for a book manually, bypassing the queue.';

    public function handle()
    {
        $bookId = $this->argument('book_id');
        $resume = $this->option('resume');
        $book = Book::find($bookId);
        
        if (!$book) {
            $this->error("Book with ID {$bookId} not found.");
            return 1;
        }
        
        if ($book->status !== 'ready_for_chunking' && $book->status !== 'extracting' && $book->status !== 'failed') {
            $this->warn("Book status is '{$book->status}'. Recommended status is 'ready_for_chunking'. Proceeding anyway...");
        }

        $this->info("Starting chunking and embedding for book: {$book->title}" . ($resume ? ' (Resuming)' : ''));
        
        $startTime = microtime(true);
        
        try {
            // Instantiate the job and run it synchronously
            $job = new IngestBookChunksJob($bookId, $resume);
            
            // Resolve dependencies from container manually since we're calling handle directly
            $parser = app(\App\Services\RAG\DocumentStructureParser::class);
            $chunkingService = app(\App\Services\RAG\DocumentChunkingService::class);
            $embeddingProvider = app(\App\Services\AI\Contracts\EmbeddingProviderInterface::class);
            
            $job->handle($parser, $chunkingService, $embeddingProvider);
            
            $duration = microtime(true) - $startTime;
            
            $this->info("Process completed successfully in " . number_format($duration, 2) . " seconds.");
            
            // Reload book to get updated status
            $book->refresh();
            $this->info("Final Book Status: {$book->status}");
            
            $chunkCount = \App\Models\ContentChunk::where('book_id', $bookId)->count();
            $this->info("Total chunks generated and embedded: {$chunkCount}");
            
        } catch (\Throwable $e) {
            $this->error("Process failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
