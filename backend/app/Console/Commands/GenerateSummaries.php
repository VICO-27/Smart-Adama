<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chapter;
use App\Models\ChapterSummary;
use App\Services\AI\Contracts\LLMGatewayInterface;
use Illuminate\Support\Facades\Log;

class GenerateSummaries extends Command
{
    protected $signature = 'rag:generate-summaries';
    protected $description = 'Pre-compute chapter summaries using Ollama to bypass CPU context limits during real-time streaming.';

    public function handle(LLMGatewayInterface $llm)
    {
        $this->info("Fetching chapters from the database...");

        $chapters = Chapter::with(['sections.contentChunks' => function ($query) {
            $query->orderBy('chunk_index');
        }])->orderBy('order')->get();

        if ($chapters->isEmpty()) {
            $this->warn("No chapters found in the database.");
            return Command::SUCCESS;
        }

        $this->info("Found {$chapters->count()} chapters. Beginning summarization...");
        $bar = $this->output->createProgressBar($chapters->count());
        $bar->start();

        foreach ($chapters as $chapter) {
            // Check if summary already exists
            if (ChapterSummary::where('chapter_number', $chapter->order)->exists()) {
                $bar->advance();
                continue;
            }

            // Gather content
            $content = '';
            $tokenCount = 0;
            $maxTokens = 2000; // Safe context limit for qwen2.5:0.5b

            foreach ($chapter->sections as $section) {
                foreach ($section->contentChunks as $chunk) {
                    if ($tokenCount > $maxTokens) {
                        break 2;
                    }
                    $content .= $chunk->chunk_text . "\n\n";
                    $tokenCount += str_word_count($chunk->chunk_text);
                }
            }

            if (empty(trim($content))) {
                $bar->advance();
                continue;
            }

            $messages = [
                ['role' => 'system', 'content' => 'You are an educational assistant. Summarize the following chapter content into a comprehensive, easy-to-read overview. Focus on the main educational points. Respond clearly and concisely.'],
                ['role' => 'user', 'content' => "Chapter Title: {$chapter->title}\n\nContent:\n{$content}"]
            ];

            try {
                // Call the LLM (synchronous)
                $summary = $llm->chat($messages, ['temperature' => 0.3, 'num_predict' => 300, 'num_thread' => 4]);

                ChapterSummary::create([
                    'chapter_number' => $chapter->order,
                    'chapter_title'  => $chapter->title ?? 'Untitled Chapter',
                    'summary_text'   => trim($summary),
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to summarize chapter {$chapter->order}", ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Chapter summaries generated successfully!");

        return Command::SUCCESS;
    }
}
