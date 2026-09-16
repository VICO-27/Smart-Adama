<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\ContentChunk;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\User;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\AI\Contracts\LLMGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class AdminSystemController extends Controller
{
    public function __invoke(
        LLMGatewayInterface $llm,
        EmbeddingProviderInterface $embedder
    ): JsonResponse {
        $checks = [];

        // Core Infrastructure
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
        }
        try {
            Cache::put('_health', true, 5);
            $checks['redis'] = 'ok';
        } catch (\Throwable $e) {
            $checks['redis'] = 'fail';
        }
        try {
            Queue::size();
            $checks['queue'] = 'ok';
        } catch (\Throwable $e) {
            $checks['queue'] = 'fail';
        }

        // AI Services
        try {
            $llmResponse = $llm->chat([['role' => 'user', 'content' => 'ping']], ['max_tokens' => 5]);
            $checks['llm'] = empty($llmResponse) ? 'fail' : 'ok';
        } catch (\Throwable $e) {
            $checks['llm'] = 'fail';
            $checks['llm_error'] = $e->getMessage();
        }

        try {
            $embeddingResponse = $embedder->embed('ping');
            $checks['embedding'] = empty($embeddingResponse) ? 'fail' : 'ok';
        } catch (\Throwable $e) {
            $checks['embedding'] = 'fail';
            $checks['embedding_error'] = $e->getMessage();
        }

        // Vector Search (pgvector test)
        try {
            DB::select('SELECT 1 FROM content_chunks ORDER BY embedding <=> (SELECT embedding FROM content_chunks WHERE embedding IS NOT NULL LIMIT 1) LIMIT 1');
            $checks['vector_search'] = 'ok';
        } catch (\Throwable $e) {
            $checks['vector_search'] = 'fail'; // pgvector might not be installed or table empty
        }

        // Stats
        $stats = [
            'books' => Book::count(),
            'chapters' => Chapter::count(),
            'sections' => Section::count(),
            'chunks' => ContentChunk::count(),
            'users' => User::count(),
            'quizAttempts' => QuizAttempt::count(),
            'ready_chunks' => ContentChunk::where('embedding_status', 'ready')->count(),
            'failed_chunks' => ContentChunk::where('embedding_status', 'failed')->count(),
        ];

        return response()->json([
            'checks' => $checks,
            'stats' => $stats,
            'providers' => [
                'llm' => config('ai.llm_provider'),
                'embedding' => config('ai.embedding_provider'),
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }
}
