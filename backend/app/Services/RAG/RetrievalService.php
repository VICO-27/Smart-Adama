<?php

namespace App\Services\RAG;

use App\Models\Book;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Retrieves the most relevant content chunks for a query using
 * pgvector cosine distance (<=> operator).
 */
class RetrievalService
{
    public function __construct(
        private readonly EmbeddingProviderInterface $embedder,
    ) {
    }

    public function search(string $originalQuery, string $normalizedQuery, int $limit = 10): \Illuminate\Support\Collection
    {
        $k_rrf = 60; // Standard RRF tuning constant

        // Embed the normalized query to get the best semantic representation
        $queryVector = $this->embedder->embed($normalizedQuery);
        $vectorStr   = '[' . implode(',', $queryVector) . ']';

        try {
            $rows = DB::select(
                <<<SQL
                WITH semantic_search AS (
                    SELECT id, (1 - (embedding <=> ?::vector)) AS score,
                           ROW_NUMBER() OVER (ORDER BY embedding <=> ?::vector) as rank
                    FROM content_chunks
                    WHERE embedding_status = 'ready'
                      AND embedding IS NOT NULL
                      AND book_id IN (SELECT id FROM books WHERE status = 'published')
                      AND (page_number IS NULL OR page_number > 8)
                      AND chunk_text NOT ILIKE '%table of contents%'
                      AND chunk_text NOT ILIKE '%list of figures%'
                      AND chunk_text NOT ILIKE '%list of tables%'
                    ORDER BY rank
                    LIMIT 60
                ),
                lexical_search AS (
                    SELECT id, GREATEST(ts_rank(search_vector, websearch_to_tsquery('english', ?)), ts_rank(search_vector, websearch_to_tsquery('english', ?))) AS score,
                           ROW_NUMBER() OVER (ORDER BY GREATEST(ts_rank(search_vector, websearch_to_tsquery('english', ?)), ts_rank(search_vector, websearch_to_tsquery('english', ?))) DESC) as rank
                    FROM content_chunks
                    WHERE embedding_status = 'ready'
                      AND book_id IN (SELECT id FROM books WHERE status = 'published')
                      AND (search_vector @@ websearch_to_tsquery('english', ?) OR search_vector @@ websearch_to_tsquery('english', ?))
                      AND (page_number IS NULL OR page_number > 8)
                      AND chunk_text NOT ILIKE '%table of contents%'
                      AND chunk_text NOT ILIKE '%list of figures%'
                      AND chunk_text NOT ILIKE '%list of tables%'
                    ORDER BY rank
                    LIMIT 60
                )
                SELECT
                    c.id, c.chunk_text, c.book_id, c.page_number, c.structural_context,
                    COALESCE(s.score, 0) as vector_score,
                    COALESCE(l.score, 0) as keyword_score,
                    COALESCE(s.rank, 1000) as vector_rank,
                    COALESCE(l.rank, 1000) as keyword_rank,
                    (
                        (CASE WHEN s.rank IS NOT NULL THEN 1.0 / (? + s.rank) ELSE 0 END) +
                        (CASE WHEN l.rank IS NOT NULL THEN 1.0 / (? + l.rank) ELSE 0 END)
                    ) as rrf_score
                FROM content_chunks c
                LEFT JOIN semantic_search s ON s.id = c.id
                LEFT JOIN lexical_search l ON l.id = c.id
                WHERE s.id IS NOT NULL OR l.id IS NOT NULL
                ORDER BY rrf_score DESC
                LIMIT ?
                SQL,
                [
                    // semantic bindings
                    $vectorStr, $vectorStr,
                    // lexical bindings (original, normalized, original, normalized, original, normalized)
                    $originalQuery, $normalizedQuery, $originalQuery, $normalizedQuery, $originalQuery, $normalizedQuery,
                    // RRF bindings
                    $k_rrf, $k_rrf,
                    // limit
                    $limit
                ]
            );
        } catch (\Throwable $e) {
            Log::error('RetrievalService: Hybrid RRF query failed', [
                'error' => $e->getMessage(),
                'query' => substr($originalQuery, 0, 80),
            ]);
            return collect([]);
        }

        $chunks = array_map(fn ($row) => [
            'id'                 => $row->id,
            'chunk_text'         => $row->chunk_text,
            'rrf_score'          => round((float) $row->rrf_score, 4),
            'vector_score'       => round((float) $row->vector_score, 4),
            'keyword_score'      => round((float) $row->keyword_score, 4),
            'vector_rank'        => (int) $row->vector_rank,
            'keyword_rank'       => (int) $row->keyword_rank,
            'book_id'            => $row->book_id,
            'page_number'        => $row->page_number,
            'structural_context' => is_string($row->structural_context) ? json_decode($row->structural_context, true) : $row->structural_context,
        ], $rows);

        Log::info('RetrievalService: retrieved chunks via Hybrid Search', [
            'query'     => substr($originalQuery, 0, 80),
            'count'     => count($chunks),
            'top_score' => $chunks[0]['rrf_score'] ?? null,
        ]);

        return collect($chunks)->values();
    }

    public function getChapterChunks(int $chapterNumber): \Illuminate\Support\Collection
    {
        $rows = DB::table('content_chunks')
            ->join('sections', 'content_chunks.section_id', '=', 'sections.id')
            ->join('chapters', 'sections.chapter_id', '=', 'chapters.id')
            ->join('books', 'content_chunks.book_id', '=', 'books.id')
            ->where('books.status', 'published')
            ->where(function($query) use ($chapterNumber) {
                $query->whereRaw("chapters.title ~* ('^' || ? || '\s+')", [$chapterNumber])
                      ->orWhere('chapters.order', $chapterNumber);
            })
            ->where('content_chunks.embedding_status', 'ready')
            ->orderBy('sections.order')
            ->orderBy('content_chunks.chunk_index')
            ->select('content_chunks.*', 'chapters.title as chapter_title', 'sections.title as section_title')
            ->get();

        return $rows->map(function ($row) {
            $context = is_string($row->structural_context) ? json_decode($row->structural_context, true) : [];
            if (!isset($context['heading'])) {
                $context['heading'] = $row->section_title ?: $row->chapter_title;
            }
            return [
                'id'                 => $row->id,
                'chunk_text'         => $row->chunk_text,
                'rrf_score'          => 1.0,
                'book_id'            => $row->book_id,
                'page_number'        => $row->page_number,
                'structural_context' => $context,
            ];
        });
    }

    public function getChunksByIds(array $ids): \Illuminate\Support\Collection
    {
        if (empty($ids)) {
            return collect();
        }
        
        $rows = DB::table('content_chunks')
            ->whereIn('id', $ids)
            ->get();

        return $rows->map(function ($row) {
            return [
                'id'                 => $row->id,
                'chunk_text'         => $row->chunk_text,
                'rrf_score'          => 0.0,
                'vector_score'       => 0.0,
                'keyword_score'      => 0.15, // Moderate continuity boost
                'book_id'            => $row->book_id,
                'page_number'        => $row->page_number,
                'structural_context' => is_string($row->structural_context) ? json_decode($row->structural_context, true) : $row->structural_context,
            ];
        });
    }
}