<?php

namespace App\Http\Controllers\Api\V1\RAG;

use App\Http\Controllers\Controller;
use App\Services\RAG\RetrievalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DebugRetrievalController extends Controller
{
    public function __construct(
        private readonly RetrievalService $retrievalService
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|max:500',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->input('query');
        $limit = (int) $request->input('limit', 10); // Default to top 10 for debug view

        $startTime = microtime(true);
        $chunks = $this->retrievalService->search($query, $limit);
        $duration = microtime(true) - $startTime;

        return response()->json([
            'query' => $query,
            'duration_seconds' => round($duration, 3),
            'results_count' => $chunks->count(),
            'chunks' => $chunks,
        ]);
    }
}
