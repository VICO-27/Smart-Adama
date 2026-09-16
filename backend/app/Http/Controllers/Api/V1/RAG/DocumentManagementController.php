<?php

namespace App\Http\Controllers\Api\V1\RAG;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DocumentManagementController extends Controller
{
    public function index(): JsonResponse
    {
        // Get all books with aggregated stats
        $books = Book::select('id', 'title', 'status', 'source_type', 'source_file_path', 'source_file_type', 'created_at', 'processing_metadata')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($book) {
                $totalPages = DB::table('book_pages')->where('book_id', $book->id)->count();
                $totalChunks = DB::table('content_chunks')->where('book_id', $book->id)->count();

                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'status' => $book->status,
                    'source_type' => $book->source_type,
                    'file_size' => $book->source_file_path ? 'N/A (Local)' : 'Unknown', // In real app, calculate from Storage::size
                    'total_pages' => $totalPages > 0 ? $totalPages : ($book->processing_metadata['pages'] ?? 0),
                    'total_chunks' => $totalChunks,
                    'created_at' => $book->created_at,
                    'processing_metadata' => $book->processing_metadata,
                ];
            });

        return response()->json([
            'documents' => $books,
        ]);
    }

    public function progress(string $id): JsonResponse
    {
        $book = Book::findOrFail($id);

        $totalPages = DB::table('book_pages')->where('book_id', $book->id)->count();
        $extractedPages = DB::table('book_pages')->where('book_id', $book->id)->whereNotNull('raw_text')->count();
        $cleanedPages = DB::table('book_pages')->where('book_id', $book->id)->whereNotNull('clean_text')->count();
        $embeddedPages = DB::table('book_pages')->where('book_id', $book->id)->where('status', 'embedded')->count();

        $totalChunks = DB::table('content_chunks')->where('book_id', $book->id)->count();
        $embeddedChunks = DB::table('content_chunks')->where('book_id', $book->id)->whereNotNull('embedding')->count();

        // Calculate progress percentage
        // Stages:
        // 1. Uploading (5%)
        // 2. Extracting (10-30%)
        // 3. Normalizing (30-50%)
        // 4. Chunking (50-70%)
        // 5. Embedding (70-95%)
        // 6. Complete (100%)

        $progress = 0;

        if ($book->status === 'uploading') {
            $progress = 5;
        } elseif ($book->status === 'extracting') {
            $progress = 10;
            if ($totalPages > 0) {
                $progress += ($extractedPages / $totalPages) * 20;
            }
        } elseif ($book->status === 'ready_for_chunking') {
            $progress = 50;
        } elseif ($book->status === 'ingesting') {
            $progress = 50;
            if ($totalChunks > 0) {
                // Approximate embedding progress
                $progress += ($embeddedChunks / $totalChunks) * 45;
            }
        } elseif (in_array($book->status, ['ingested', 'ready', 'published'])) {
            $progress = 100;
        } elseif ($book->status === 'failed' || $book->status === 'ocr_required') {
            $progress = 100; // Stop progress bar
        }

        return response()->json([
            'id' => $book->id,
            'status' => $book->status,
            'progress_percent' => min(100, round($progress)),
            'metrics' => [
                'total_pages' => $totalPages,
                'extracted_pages' => $extractedPages,
                'cleaned_pages' => $cleanedPages,
                'embedded_pages' => $embeddedPages,
                'total_chunks' => $totalChunks,
                'embedded_chunks' => $embeddedChunks,
            ],
            'metadata' => $book->processing_metadata,
        ]);
    }
}
