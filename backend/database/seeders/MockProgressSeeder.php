<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Book;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserProgress;
use App\Models\UserStreak;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class MockProgressSeeder extends Seeder
{
    public function run(): void
    {
        // Get the latest user (the one they are currently testing with)
        $user = User::latest()->first();

        if (! $user) {
            $this->command->error('No users found. Please register an account first.');

            return;
        }

        $this->command->info("Seeding mock progress for user: {$user->email} ({$user->id})");

        // Ensure there is a canonical book
        $book = Book::canonical();
        if (! $book) {
            $book = Book::firstOrCreate(
                ['status' => 'published'],
                ['title' => 'Smart Adama: Official Handbook', 'is_canonical' => true]
            );
        }

        // Ensure there are at least 11 chapters
        if ($book->chapters()->count() < 11) {
            for ($i = 1; $i <= 11; $i++) {
                $book->chapters()->firstOrCreate(
                    ['order' => $i],
                    [
                        'title' => "Chapter $i",
                        'ingestion_status' => 'ready',
                    ]
                );
            }
        }

        $chapters = $book->chapters()->orderBy('order')->get();

        // 1. Mark some chapters as completed (e.g., first 5)
        $completedCount = 5;
        foreach ($chapters->take($completedCount) as $chapter) {
            UserProgress::updateOrCreate(
                ['user_id' => $user->id, 'chapter_id' => $chapter->id],
                [
                    'is_completed' => true,
                    'best_quiz_score_pct' => rand(70, 100),
                    'last_read_at' => Carbon::now()->subDays(rand(0, 5)),
                ]
            );
        }

        // 2. Create some quiz attempts
        foreach ($chapters->take($completedCount) as $chapter) {
            $quiz = Quiz::firstOrCreate(
                ['chapter_id' => $chapter->id],
                ['title' => "Quiz for {$chapter->title}", 'passing_score_pct' => 70, 'status' => 'published']
            );

            QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score_pct' => rand(75, 100),
                'passed' => true,
                'started_at' => Carbon::now()->subDays(rand(1, 5)),
                'submitted_at' => Carbon::now()->subDays(rand(1, 5)),
            ]);
        }

        // 3. Set a streak
        UserStreak::updateOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak' => 12,
                'longest_streak' => 24,
                'last_activity_date' => Carbon::today(),
            ]
        );

        // 4. Award some badges
        $badges = [
            ['code' => 'PIONEER', 'name' => 'Pioneer', 'description' => 'Joined early.', 'icon' => '🌟', 'criteria' => []],
            ['code' => 'SCHOLAR', 'name' => 'Scholar', 'description' => 'Passed 5 quizzes.', 'icon' => '📚', 'criteria' => []],
            ['code' => 'STREAK_7', 'name' => '7 Day Streak', 'description' => 'Studied 7 days in a row.', 'icon' => '🔥', 'criteria' => []],
        ];

        foreach ($badges as $badgeData) {
            $badge = Badge::firstOrCreate(
                ['code' => $badgeData['code']],
                $badgeData
            );

            UserBadge::updateOrCreate(
                ['user_id' => $user->id, 'badge_id' => $badge->id],
                ['awarded_at' => Carbon::now()->subDays(rand(1, 10))]
            );
        }

        // Clear dashboard cache to ensure the frontend gets the fresh data immediately
        Cache::forget("dashboard:{$user->id}");

        $this->command->info('Mock progress seeded successfully!');
    }
}
