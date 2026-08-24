<?php

namespace App\Services\RAG;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use App\Models\Book;
use App\Models\BookPage;

class PdfExtractionService
{
    public function __construct(
        private readonly DocumentNormalizationService $normalizer
    ) {}

    /**
     * Parses a PDF file page by page, normalizes, and stores to book_pages.
     * Returns an array with stats.
     */
    public function extractAndStore(Book $book): array
    {
        $absolutePath = Storage::disk('local')->path($book->source_file_path);
        
        if (!file_exists($absolutePath)) {
            throw new \Exception("PDF file not found at path: {$absolutePath}");
        }

        try {
            $book->logProgress('Extracting text via safe process pdftotext...', 'info');
            
            // Execute pdftotext safely using Laravel Process
            $result = Process::timeout(300)->run([
                'pdftotext',
                '-layout',
                $absolutePath,
                '-'
            ]);
            
            if (!$result->successful()) {
                throw new \Exception("pdftotext failed: " . $result->errorOutput());
            }

            $text = $result->output();
            
            if (empty(trim($text))) {
                throw new \Exception("pdftotext returned empty output. Ensure poppler-utils is installed and PDF is valid.");
            }

            // pdftotext uses \x0C (form feed) as a page separator
            $rawPages = explode("\x0C", $text);
            
            // pdftotext often adds a trailing \x0C at the very end of the file, resulting in an empty last page
            if (trim(end($rawPages)) === '') {
                array_pop($rawPages);
            }
            
            $book->logProgress('PDF parsed via pdftotext. Extracted ' . count($rawPages) . ' raw pages.', 'info');
            
            $pageTexts = [];
            foreach ($rawPages as $index => $page) {
                $pageTexts[$index + 1] = $page;
            }

            // Detect empty or heavily malformed PDF
            $totalOriginalChars = array_sum(array_map('strlen', $pageTexts));
            if ($totalOriginalChars < 100 && count($rawPages) > 0) {
                return ['status' => 'ocr_required', 'error' => 'Extracted text is suspiciously short.'];
            }

            // Remove headers/footers
            $book->logProgress('Removing headers and footers and normalizing text...', 'info');
            $cleanedPagesText = $this->normalizer->removePageArtifacts(array_values($pageTexts));

            // Clean up old pages if re-ingesting
            BookPage::where('book_id', $book->id)->delete();

            $totalCleanedChars = 0;
            $suspiciousPages = 0;

            foreach ($pageTexts as $pageNumber => $rawText) {
                // The array_values is 0-indexed, so $pageNumber - 1
                $cleanedTextIndex = $pageNumber - 1;
                $cleanedText = $cleanedPagesText[$cleanedTextIndex] ?? '';
                $normalizedText = $this->normalizer->normalize($cleanedText);
                
                $charCount = strlen($normalizedText);
                $totalCleanedChars += $charCount;

                // Simple heuristic for suspicious page (lots of non-ascii or very short text inside a big doc)
                $isSuspicious = false;
                if ($charCount < 50 && strlen(trim($rawText)) > 200) {
                    $isSuspicious = true;
                }
                
                if ($isSuspicious) {
                    $suspiciousPages++;
                }

                BookPage::create([
                    'book_id' => $book->id,
                    'page_number' => $pageNumber,
                    'raw_text' => $rawText,
                    'clean_text' => $normalizedText,
                    'is_suspicious' => $isSuspicious,
                    'status' => $charCount > 0 ? 'ready' : 'blank',
                ]);
            }

            $book->logProgress("Extraction complete. Processed $totalCleanedChars characters.", 'success');

            return [
                'status' => 'ready_for_chunking', // The Phase 2 boundary status
                'pages' => count($rawPages),
                'original_chars' => $totalOriginalChars,
                'cleaned_chars' => $totalCleanedChars,
                'suspicious_pages' => $suspiciousPages,
            ];

        } catch (\Exception $e) {
            Log::error("PdfExtractionService: Failed to parse PDF", ['error' => $e->getMessage()]);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }
}
