<?php

namespace App\Services\Progress;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Cache;

class ProgressService
{
    /**
     * Mark chapter as read/update reading progress (Req 10.2).
     */
    public function updateReadingProgress(User $user, Chapter $chapter, int $readingProgress, ?string $lastPage = null): UserProgress
    {
        $progress = UserProgress::firstOrCreate(
            ['user_id' => $user->id, 'chapter_id' => $chapter->id],
            ['status' => 'NOT_STARTED', 'reading_progress' => 0]
        );

        $status = $progress->status;
        if ($status === 'NOT_STARTED') {
            $status = 'IN_PROGRESS';
        }

        $readingProgress = max($readingProgress, $progress->reading_progress ?? 0);
        $bestScore = $progress->best_quiz_score_pct ?? 0;

        if ($readingProgress >= 100) {
            $status = 'COMPLETED';
        }

        $progress->update([
            'reading_progress' => $readingProgress,
            'last_page' => $lastPage ?? $progress->last_page,
            'status' => $status,
            'started_at' => $progress->started_at ?? now(),
            'last_read_at' => now(),
            'completed_at' => $status === 'COMPLETED' ? ($progress->completed_at ?? now()) : null,
        ]);

        $this->invalidateDashboardCache($user->id);

        return $progress->fresh();
    }

    /**
     * Mark chapter as completed based on quiz results (Req 10.2).
     */
    public function markChapterComplete(User $user, Chapter $chapter, float $scorePct): UserProgress
    {
        $progress = UserProgress::firstOrCreate(
            ['user_id' => $user->id, 'chapter_id' => $chapter->id],
            ['status' => 'NOT_STARTED', 'reading_progress' => 0]
        );

        $bestScore = max($scorePct, $progress->best_quiz_score_pct ?? 0);

        $quiz = $chapter->quiz;
        $passingScore = $quiz ? $quiz->passing_score_pct : 70;

        $status = $progress->status;
        $readingProgress = $progress->reading_progress ?? 0;

        if ($readingProgress >= 100) {
            $status = 'COMPLETED';
        } elseif ($status === 'NOT_STARTED') {
            $status = 'IN_PROGRESS';
        }

        $progress->update([
            'best_quiz_score_pct' => $bestScore,
            'status' => $status,
            'completed_at' => $status === 'COMPLETED' ? ($progress->completed_at ?? now()) : null,
            'last_read_at' => now(),
        ]);

        $this->invalidateDashboardCache($user->id);

        return $progress->fresh();
    }

    /**
     * Get single authoritative progress summary.
     */
    public function getSummary(User $user): array
    {
        $cacheKey = "progress_summary:{$user->id}";

        return Cache::remember($cacheKey, 60, function () use ($user) {
            $canonicalBook = Book::withCount('chapters')->orderByDesc('chapters_count')->first();

            $canonicalChapterIds = $canonicalBook
                ? $canonicalBook->chapters()->pluck('id')
                : Chapter::pluck('id');

            $totalChapters = $canonicalChapterIds->count();

            $progressRecords = UserProgress::where('user_id', $user->id)
                ->whereIn('chapter_id', $canonicalChapterIds)
                ->with('chapter')
                ->get();

            // A. OVERALL COMPLETION
            $completedChapters = $progressRecords->where('status', 'COMPLETED')->count();
            $overallPercentage = $totalChapters > 0 ? round(($completedChapters / $totalChapters) * 100, 1) : 0;

            // C. MASTERY
            $quizzesCompleted = QuizAttempt::where('user_id', $user->id)
                ->whereIn('quiz_id', function ($query) use ($canonicalChapterIds) {
                    $query->select('id')->from('quizzes')->whereIn('chapter_id', $canonicalChapterIds);
                })
                ->where('passed', true)
                ->distinct('quiz_id')
                ->count('quiz_id');

            $avgScore = $progressRecords->whereNotNull('best_quiz_score_pct')->avg('best_quiz_score_pct');

            // B. CURRENT POSITION
            $lastAccessedRecord = $progressRecords->sortByDesc('last_read_at')->first();
            $currentChapter = $lastAccessedRecord ? $lastAccessedRecord->chapter : null;
            $currentPosition = $lastAccessedRecord ? $lastAccessedRecord->last_page : null;

            // Chapter-by-chapter progress
            $chapterProgress = $canonicalChapterIds->map(function ($chapterId) use ($progressRecords) {
                $record = $progressRecords->firstWhere('chapter_id', $chapterId);

                return [
                    'chapter_id' => $chapterId,
                    'status' => $record ? $record->status : 'NOT_STARTED',
                    'reading_progress' => $record ? $record->reading_progress : 0,
                    'best_quiz_score_pct' => $record ? $record->best_quiz_score_pct : null,
                ];
            })->values()->toArray();

            return [
                'overallPercentage' => $overallPercentage,
                'completedChapters' => $completedChapters,
                'totalChapters' => $totalChapters,
                'currentChapter' => $currentChapter ? $currentChapter->toArray() : null,
                'currentPosition' => $currentPosition,
                'chapterProgress' => $chapterProgress,
                'averageQuizScore' => $avgScore ? round($avgScore, 1) : null,
                'quizzesCompleted' => $quizzesCompleted,
            ];
        });
    }

    public function invalidateDashboardCache(string $userId): void
    {
        Cache::forget("progress_summary:{$userId}");
        Cache::forget("dashboard:{$userId}");
    }
}
