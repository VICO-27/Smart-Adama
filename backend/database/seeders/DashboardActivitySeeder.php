<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardActivitySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->toArray();
        if (empty($users)) {
            // fallback if no users
            $users = [1, 2, 3];
        }

        $quizzes = Quiz::pluck('id')->toArray();
        if (empty($quizzes)) {
            $chapter = Chapter::first();
            if ($chapter) {
                $quiz = Quiz::create(['chapter_id' => $chapter->id, 'title' => 'Sample', 'description' => 'x']);
                $quizzes = [$quiz->id];
            } else {
                $quizzes = [1];
            }
        }

        // We want realistic sweeping curves.
        // Let's model a growth trend with some variance.

        $startDate = Carbon::now()->subDays(29)->startOfDay();

        $quizAttempts = [];
        $chatSessions = [];
        $chatMessages = [];

        $baseQuizzes = 50;
        $baseQueries = 100;

        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->clone()->addDays($i);

            // create sweeping curves (e.g., sin wave + growth)
            $growthMultiplier = 1 + ($i / 15); // slowly doubles over 30 days
            $wave = sin($i / 3) * 0.5 + 1; // 0.5 to 1.5

            $dayQuizzes = (int) ($baseQuizzes * $growthMultiplier * $wave * mt_rand(80, 120) / 100);
            $dayQueries = (int) ($baseQueries * $growthMultiplier * ($wave + 0.5) * mt_rand(80, 120) / 100);

            for ($q = 0; $q < $dayQuizzes; $q++) {
                $createdAt = $date->clone()->addMinutes(mt_rand(0, 1400));
                $quizAttempts[] = [
                    'id' => Str::uuid(),
                    'user_id' => $users[array_rand($users)],
                    'quiz_id' => $quizzes[array_rand($quizzes)],
                    'score_pct' => mt_rand(40, 100),
                    'passed' => mt_rand(0, 1) === 1,
                    'started_at' => $createdAt->clone()->subMinutes(10),
                    'submitted_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            for ($c = 0; $c < $dayQueries; $c++) {
                $createdAt = $date->clone()->addMinutes(mt_rand(0, 1400));
                $sessionId = Str::uuid();

                $chatSessions[] = [
                    'id' => $sessionId,
                    'user_id' => $users[array_rand($users)],
                    'title' => 'Dashboard seeded session',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                $chatMessages[] = [
                    'id' => Str::uuid(),
                    'chat_session_id' => $sessionId,
                    'role' => 'user',
                    'content' => 'Sample seeded query',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
                $chatMessages[] = [
                    'id' => Str::uuid(),
                    'chat_session_id' => $sessionId,
                    'role' => 'assistant',
                    'content' => 'Sample seeded response',
                    'created_at' => $createdAt->clone()->addSeconds(2),
                    'updated_at' => $createdAt->clone()->addSeconds(2),
                ];
            }
        }

        // Insert in chunks
        foreach (array_chunk($quizAttempts, 500) as $chunk) {
            DB::table('quiz_attempts')->insert($chunk);
        }

        foreach (array_chunk($chatSessions, 500) as $chunk) {
            DB::table('chat_sessions')->insert($chunk);
        }

        foreach (array_chunk($chatMessages, 500) as $chunk) {
            DB::table('chat_messages')->insert($chunk);
        }
    }
}
