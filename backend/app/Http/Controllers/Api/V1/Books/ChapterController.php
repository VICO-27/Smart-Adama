<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChapterResource;
use App\Models\Chapter;
use App\Services\Progress\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Learner chapter endpoints (Req 3.7, 10.1)
 */
class ChapterController extends Controller
{
    public function __construct(private readonly ProgressService $progressService) {}

    /**
     * GET /chapters/{chapter}
     * Chapter detail + sections for the learner reading view.
     * Includes the current user's progress for this chapter.
     */
    public function show(Request $request, Chapter $chapter): JsonResponse
    {
        $chapter->load(['sections' => function ($query) {
            $query->orderBy('order');
        }]);

        $progress = $request->user()
            ->progress()
            ->where('chapter_id', $chapter->id)
            ->first();

        return response()->json([
            'chapter' => new ChapterResource($chapter),
            'progress' => $progress ? [
                'status' => $progress->status,
                'reading_progress' => $progress->reading_progress,
                'best_quiz_score_pct' => $progress->best_quiz_score_pct,
                'last_read_at' => $progress->last_read_at?->toISOString(),
                'last_page' => $progress->last_page,
            ] : null,
        ]);
    }

    /**
     * POST /chapters/{chapter}/read
     * Update reading progress (Req 10.1).
     */
    public function markRead(Request $request, Chapter $chapter): JsonResponse
    {
        $readingProgress = $request->input('reading_progress', 100);
        $lastPage = $request->input('last_page');

        $this->progressService->updateReadingProgress($request->user(), $chapter, (int) $readingProgress, $lastPage);

        return response()->json(['message' => 'Reading progress updated.']);
    }
}
