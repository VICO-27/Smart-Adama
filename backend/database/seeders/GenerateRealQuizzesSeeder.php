<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\QuizQuestion;

class GenerateRealQuizzesSeeder extends Seeder
{
    public function run(): void
    {
        // Get up to 11 chapters ordered by their sequence
        $chapters = Chapter::orderBy('order')->limit(11)->get();

        $this->command->info("Found " . $chapters->count() . " chapters.");

        foreach ($chapters as $index => $chapter) {
            $realChapterNum = $index + 1;

            // Create Quiz
            $quiz = Quiz::firstOrCreate(
                ['chapter_id' => $chapter->id],
                [
                    'title' => 'Chapter ' . $realChapterNum . ' Quiz: ' . $chapter->title,
                    'passing_score_pct' => 70,
                    'status' => 'published',
                ]
            );

            // Generate exactly 5 questions
            for ($q = 1; $q <= 5; $q++) {
                $question = QuizQuestion::firstOrCreate(
                    ['quiz_id' => $quiz->id, 'order' => $q],
                    [
                        'question_text' => 'Sample question ' . $q . ' for ' . $chapter->title . '?',
                        'type' => 'single',
                    ]
                );

                if ($question->options()->count() === 0) {
                    $question->options()->create([
                        'option_text' => 'Correct Option',
                        'order' => 1,
                        'is_correct' => true,
                    ]);
                    $question->options()->create([
                        'option_text' => 'Incorrect Option 1',
                        'order' => 2,
                        'is_correct' => false,
                    ]);
                    $question->options()->create([
                        'option_text' => 'Incorrect Option 2',
                        'order' => 3,
                        'is_correct' => false,
                    ]);
                    $question->options()->create([
                        'option_text' => 'Incorrect Option 3',
                        'order' => 4,
                        'is_correct' => false,
                    ]);
                }
            }
        }

        $this->command->info('Generated quizzes for ' . $chapters->count() . ' chapters with 5 questions each.');
    }
}
