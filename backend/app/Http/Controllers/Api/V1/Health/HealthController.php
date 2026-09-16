<?php

namespace App\Http\Controllers\Api\V1\Health;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Models\ContentChunk;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * GET /api/v1/health
 * Public endpoint — reports DB, cache, and queue connectivity.
 * Requirement 18.5
 */
class HealthController extends Controller
{
    public function __invoke()
    {
        $checks = [];

        // Database
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
        }

        // Cache (Redis)
        try {
            Cache::put('_health_check', true, 5);
            Cache::get('_health_check');
            $checks['cache'] = 'ok';
        } catch (\Throwable $e) {
            $checks['cache'] = 'fail';
        }

        // Queue (Redis)
        try {
            $size = Queue::size();
            $checks['queue'] = 'ok';
        } catch (\Throwable $e) {
            $checks['queue'] = 'fail';
        }

        $checks['application'] = 'ok';

        // Vector store (pgvector)
        try {
            DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector'");
            $checks['vector_store'] = 'ok';
        } catch (\Throwable $e) {
            $checks['vector_store'] = 'fail';
        }

        // Providers
        $checks['gemini_llm'] = ! empty(config('ai.gemini.api_key')) ? 'configured' : 'missing_key';
        $checks['groq_llm'] = ! empty(config('ai.groq.api_key')) ? 'configured' : 'missing_key';
        $checks['voyage_embedding'] = ! empty(config('ai.voyage.api_key')) ? 'configured' : 'missing_key';
        $checks['gemini_embedding'] = ! empty(config('ai.gemini.api_key')) ? 'configured' : 'missing_key';

        try {
            $response = Http::timeout(2)->get(config('ai.ollama.llm_base_url'));
            $checks['ollama_llm'] = $response->successful() ? 'available' : 'unavailable';
            $checks['ollama_embedding'] = $response->successful() ? 'available' : 'unavailable';
        } catch (\Throwable $e) {
            $checks['ollama_llm'] = 'unavailable';
            $checks['ollama_embedding'] = 'unavailable';
        }

        // Active State
        $checks['active_llm_provider'] = AdminSetting::where('key', 'ai_llm_provider')->first()?->value ?: config('ai.llm_provider', 'gemini');
        $checks['active_embedding_provider'] = AdminSetting::where('key', 'ai_embedding_provider')->first()?->value ?: config('ai.embedding_provider', 'voyage');

        $checks['active_vector_index'] = ContentChunk::whereNotNull('embedding')
            ->where('embedding_status', 'ready')
            ->first()?->embedding_provider ?? 'none';

        $allOk = ! in_array('fail', $checks, true);

        return response()->json([
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ], $allOk ? 200 : 503);
    }
}
