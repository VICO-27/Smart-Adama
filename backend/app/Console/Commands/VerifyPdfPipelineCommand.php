<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\RAG\DocumentNormalizationService;
use App\Services\RAG\PdfExtractionService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class VerifyPdfPipelineCommand extends Command
{
    protected $signature = 'rag:verify-pdf {path}';

    protected $description = 'Run the Phase 2 PDF pipeline on a local file to verify extraction and normalization.';

    public function handle()
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File not found at: {$path}");

            return 1;
        }

        $this->info("Verifying PDF pipeline for: {$path}");

        $startTime = microtime(true);
        $fileSize = filesize($path);

        // Mock a Book for the extraction service (without saving)
        $book = new Book;
        $book->id = (string) Str::uuid();
        $book->source_file_path = $path; // Extraction service will use Storage::disk('local')->path(), so we need to copy it there if it's not

        // Ensure file is in local storage for testing
        $storagePath = storage_path('app/manuscripts/test_verify.pdf');
        @mkdir(dirname($storagePath), 0755, true);
        copy($path, $storagePath);

        $book->source_file_path = 'manuscripts/test_verify.pdf';

        $parser = new Parser;
        $normalizer = new DocumentNormalizationService;
        $extractor = new PdfExtractionService($parser, $normalizer);

        $this->info('Running extraction...');

        try {
            $result = $extractor->extractAndStore($book);
            $duration = microtime(true) - $startTime;

            $this->info('----------------------------------------');
            $this->info('Phase 2 PDF Pipeline Verification Report');
            $this->info('----------------------------------------');
            $this->info('File Size: '.number_format($fileSize / 1024, 2).' KB');
            $this->info('Pages Extracted: '.($result['pages'] ?? 0));
            $this->info('Original Characters: '.number_format($result['original_chars'] ?? 0));
            $this->info('Cleaned Characters: '.number_format($result['cleaned_chars'] ?? 0));
            $this->info('Suspicious Pages: '.($result['suspicious_pages'] ?? 0));
            $this->info('Final Status: '.($result['status'] ?? 'unknown'));
            $this->info('Processing Duration: '.number_format($duration, 2).' seconds');
            $this->info('----------------------------------------');

            if (isset($result['error'])) {
                $this->error('Pipeline reported error: '.$result['error']);
            } else {
                $this->info('Pipeline completed successfully! Clean pages are stored in the book_pages table.');
            }

            // Clean up test file
            @unlink($storagePath);

        } catch (\Exception $e) {
            $this->error('Pipeline crashed: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
