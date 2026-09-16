<?php

namespace App\Services\RAG;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class PdfParserService
{
    public function __construct(private readonly Parser $parser) {}

    /**
     * Parses a PDF file from storage and returns normalized raw text.
     */
    public function parseFromStorage(string $filePath): string
    {
        // Make sure it's stored on local disk
        $absolutePath = Storage::disk('local')->path($filePath);

        if (! file_exists($absolutePath)) {
            Log::error('PdfParserService: File not found', ['path' => $absolutePath]);
            throw new \Exception("PDF file not found at path: {$absolutePath}");
        }

        try {
            $pdf = $this->parser->parseFile($absolutePath);
            $rawText = $pdf->getText();

            return $this->normalizeText($rawText);
        } catch (\Exception $e) {
            Log::error('PdfParserService: Failed to parse PDF', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to parse PDF: '.$e->getMessage());
        }
    }

    /**
     * Cleans up common PDF extraction anomalies like weird spacing,
     * random null characters, and excessive line breaks.
     */
    public function normalizeText(string $text): string
    {
        // Remove null bytes and form feeds
        $text = preg_replace('/[\x00\x0C]/', '', $text);

        // Normalize line endings to \n
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove page numbers that appear alone on a line (crude heuristic)
        $text = preg_replace('/^\s*\d+\s*$/m', '', $text);

        // Collapse 3 or more consecutive newlines into exactly 2
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Splits the entire book text into chapters.
     * Returns an associative array where key is chapter number and value is the chapter text.
     */
    public function extractChapters(string $text): array
    {
        $chapters = [];

        // Regex matches lines starting with "Chapter N" and captures everything until the next one.
        // Using `m` (multiline) and `s` (dot matches newline) modifiers.
        preg_match_all('/^\s*Chapter\s+(\d+)\b(.*?)(?=^\s*Chapter\s+\d+\b|\z)/ims', $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $chapterNumber = (int) $match[1];
            $chapterContent = trim($match[0]); // Includes "Chapter X" heading and the text

            // Keep all chapters found in the document
            $chapters[$chapterNumber] = $chapterContent;
        }

        return $chapters;
    }
}
