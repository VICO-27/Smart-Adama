<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserProgress;
use App\Models\UserStreak;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FakeUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating fake users with progress...");

        // Ensure there is a canonical book
        $book = Book::canonical();
        if (!$book) {
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
                        'ingestion_status' => 'ready'
                    ]
                );
            }
        }

        $chapters = $book->chapters()->orderBy('order')->get();
        $statuses = ['active', 'active', 'active', 'inactive', 'suspended'];

        for ($i = 0; $i < 15; $i++) {
            $user = User::factory()->create([
                'status' => $statuses[array_rand($statuses)]
            ]);

            // Give them some random progress
            $completedCount = rand(0, 11);
            if ($completedCount > 0) {
                foreach ($chapters->take($completedCount) as $chapter) {
                    UserProgress::updateOrCreate(
                        ['user_id' => $user->id, 'chapter_id' => $chapter->id],
                        [
                            'is_completed' => true,
                            'best_quiz_score_pct' => rand(60, 100),
                            'last_read_at' => Carbon::now()->subDays(rand(0, 30))
                        ]
                    );

                    $quiz = Quiz::firstOrCreate(
                        ['chapter_id' => $chapter->id],
                        ['title' => "Quiz for {$chapter->title}", 'passing_score_pct' => 70, 'status' => 'published']
                    );

                    QuizAttempt::create([
                        'user_id' => $user->id,
                        'quiz_id' => $quiz->id,
                        'score_pct' => rand(60, 100),
                        'passed' => true,
                        'started_at' => Carbon::now()->subDays(rand(1, 30)),
                        'submitted_at' => Carbon::now()->subDays(rand(1, 30)),
                    ]);
                }
            }

            // Set a random streak
            if (rand(0, 1)) {
                UserStreak::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'current_streak' => rand(1, 30),
                        'longest_streak' => rand(5, 50),
                        'last_activity_date' => Carbon::today()->subDays(rand(0, 2))
                    ]
                );
            }
        }

        $this->command->info("Created 15 fake users with realistic progress!");
    }
}
