<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Learner dashboard — single cached payload (Req 12.1, 12.2, 12.3)
 * Cache TTL: 60 seconds, invalidated by ProgressService on any write.
 */
class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user     = $request->user();
        $cacheKey = "dashboard:{$user->id}";

        $data = Cache::remember($cacheKey, 60, function () use ($user) {
            $progressService = app(\App\Services\Progress\ProgressService::class);
            $summary = $progressService->getSummary($user);

            $streak        = $user->streak;
            $currentStreak = $streak?->current_streak ?? 0;

            $chatCount  = $user->chatSessions()->count();
            $badgeCount = $user->badges()->count();

            return [
                'completion_pct'     => $summary['overallPercentage'],
                'total_chapters'     => $summary['totalChapters'],
                'completed_chapters' => $summary['completedChapters'],
                'quizzes_passed'     => $summary['quizzesCompleted'],
                'average_quiz_score' => $summary['averageQuizScore'],
                'current_streak'     => $currentStreak,
                'total_chat_sessions' => $chatCount,
                'earned_badge_count' => $badgeCount,
                'current_chapter'    => $summary['currentChapter'],
                'current_position'   => $summary['currentPosition'],
                'chapter_progress'   => $summary['chapterProgress'],
            ];
        });

        return response()->json(['dashboard' => $data]);
    }
}