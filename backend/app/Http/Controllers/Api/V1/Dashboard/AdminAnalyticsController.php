<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin aggregate platform analytics (Req 12.4 — P2)
 * Cache TTL: 60 seconds.
 */
class AdminAnalyticsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $data = Cache::remember('admin_analytics', 60, function () {
            $totalUsers        = User::whereNull('deleted_at')->count();
            $totalChapters     = Chapter::count();

            $completedProgress = UserProgress::where('is_completed', true)->count();
            $avgCompletion     = $totalUsers > 0 && $totalChapters > 0
                ? round(($completedProgress / ($totalUsers * $totalChapters)) * 100, 1)
                : 0;

            $attempts      = QuizAttempt::whereNotNull('submitted_at');
            $totalAttempts = (clone $attempts)->count();
            $passedAttempts = (clone $attempts)->where('passed', true)->count();
            $avgScore      = $totalAttempts > 0
                ? round((clone $attempts)->avg('score_pct'), 1)
                : null;

            $totalBadgesAwarded = UserBadge::count();

            $totalBooks = \App\Models\Book::count();
            $totalChunks = \App\Models\ContentChunk::count();

            // Real trend calculations
            $usersLastMonthCount = User::whereNull('deleted_at')->where('created_at', '<', now()->subMonth())->count();
            $usersTrendPct = $usersLastMonthCount > 0 ? round((($totalUsers - $usersLastMonthCount) / $usersLastMonthCount) * 100, 1) : 0;

            $booksThisWeek = \App\Models\Book::where('created_at', '>=', now()->subWeek())->count();
            $chunksSinceYesterday = \App\Models\ContentChunk::where('created_at', '>=', now()->subDay())->count();

            $timeline = [];
            $startDate = now()->subDays(29)->startOfDay();
            for ($i = 0; $i < 30; $i++) {
                $dateStr = $startDate->clone()->addDays($i)->format('Y-m-d');
                $timeline[$dateStr] = [
                    'date' => $dateStr,
                    'users' => 0,
                    'queries' => 0,
                    'quizzes' => 0,
                ];
            }

            // Fill Queries (chat messages)
            $queries = \App\Models\ChatMessage::where('created_at', '>=', $startDate)
                ->where('role', 'user')
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date');

            // Fill Quizzes
            $quizzes = \App\Models\QuizAttempt::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date');

            // Calculate Active Users (mock based on active users that day or just general activity)
            // Let's use active distinct users from both tables
            $activeUsers = \Illuminate\Support\Facades\DB::table('chat_sessions')
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT user_id) as count')
                ->groupBy('date')
                ->pluck('count', 'date');

            foreach ($timeline as $date => &$tData) {
                $tData['queries'] = $queries[$date] ?? 0;
                $tData['quizzes'] = $quizzes[$date] ?? 0;
                $tData['users'] = $activeUsers[$date] ?? 0;
            }

            return [
                'total_users'           => $totalUsers,
                'users_trend_pct'       => $usersTrendPct,
                'total_books'           => $totalBooks,
                'books_this_week'       => $booksThisWeek,
                'total_chunks'          => $totalChunks,
                'chunks_since_yesterday'=> $chunksSinceYesterday,
                'total_chapters'        => $totalChapters,
                'avg_completion_pct'    => $avgCompletion,
                'total_quiz_attempts'   => $totalAttempts,
                'total_quizzes_passed'  => $passedAttempts,
                'avg_quiz_score'        => $avgScore,
                'total_badges_awarded'  => $totalBadgesAwarded,
                'timeline'              => array_values($timeline)
            ];
        });

        return response()->json(['analytics' => $data]);
    }
}
