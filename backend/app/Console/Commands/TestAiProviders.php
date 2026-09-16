<?php

namespace App\Console\Commands;

use App\Models\AdminSetting;
use App\Services\AI\LLMProviderManager;
use App\Services\RAG\RetrievalService;
use Illuminate\Console\Command;

class TestAiProviders extends Command
{
    protected $signature = 'test:ai';

    protected $description = 'Test Gemini, Groq, and Ollama LLM providers';

    public function handle(LLMProviderManager $llmManager, RetrievalService $retrieval)
    {
        $messages = [
            ['role' => 'user', 'content' => 'Say the word "test" and nothing else.'],
        ];

        // 1. Test raw LLM Providers via config switching
        $this->info('--- 1. Testing Raw LLM Switcher ---');
        foreach (['gemini', 'groq', 'ollama'] as $provider) {
            AdminSetting::updateOrCreate(['key' => 'ai_llm_provider'], ['value' => $provider]);
            $this->info("Testing Active LLM: {$provider}...");
            try {
                $response = $llmManager->chat($messages);
                $this->info("{$provider} response: ".trim($response));
            } catch (\Exception $e) {
                $this->error("{$provider} failed: ".$e->getMessage());
            }
        }

        // 2. Test Embedding Switching & Protection
        $this->info("\n--- 2. Testing Embedding Mismatch Protection ---");
        $question = 'What is the capital of Smart Adama?';

        AdminSetting::updateOrCreate(['key' => 'ai_embedding_provider'], ['value' => 'gemini']);
        $this->info('Active Embedding: gemini. Querying existing voyage chunks...');
        try {
            $results = $retrieval->search($question, $question);
            $this->info('Results count: '.$results->count().' (Should be 0 if chunks are voyage)');
        } catch (\Exception $e) {
            $this->error('Search failed: '.$e->getMessage());
        }

        AdminSetting::updateOrCreate(['key' => 'ai_embedding_provider'], ['value' => 'ollama']);
        $this->info('Active Embedding: ollama. Querying existing ollama chunks...');
        try {
            $results = $retrieval->search($question, $question);
            $this->info('Results count: '.$results->count().' (Should be > 0 if ollama chunks exist)');
        } catch (\Exception $e) {
            $this->error('Search failed: '.$e->getMessage());
        }

        // 3. Test Full RAG Flow (Ollama + Gemini)
        $this->info("\n--- 3. Testing Full RAG Flow (Ollama + Gemini) ---");
        AdminSetting::updateOrCreate(['key' => 'ai_llm_provider'], ['value' => 'gemini']);
        AdminSetting::updateOrCreate(['key' => 'ai_embedding_provider'], ['value' => 'ollama']);

        $context = $results->pluck('chunk_text')->take(3)->implode("\n\n");
        $ragMessages = [
            ['role' => 'system', 'content' => "Use context to answer: {$context}"],
            ['role' => 'user', 'content' => $question],
        ];
        try {
            $response = $llmManager->chat($ragMessages);
            $this->info('RAG Response: '.trim($response));
        } catch (\Exception $e) {
            $this->error('RAG failed: '.$e->getMessage());
        }

        return 0;
    }
}
