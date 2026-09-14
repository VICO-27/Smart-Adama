<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Models\AdminSetting;
use App\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Log;

class EmbeddingProviderManager implements EmbeddingProviderInterface
{
    private array $providers = [];

    public function __construct(
        private GeminiEmbeddingProvider $gemini,
        private VoyageEmbeddingProvider $voyage,
        private OllamaEmbeddingProvider $ollama
    ) {
        $this->providers = [
            'gemini' => $gemini,
            'voyage' => $voyage,
            'ollama' => $ollama,
        ];
    }

    private function getActiveProvider(): string
    {
        // Provider configuration priority: Environment -> Admin -> Active
        // Default to env, override by DB setting.
        $envDefault = config('ai.embedding_provider', 'voyage');
        try {
            $setting = AdminSetting::where('key', 'ai_embedding_provider')->first();
            return $setting ? $setting->value : $envDefault;
        } catch (\Throwable) {
            return $envDefault;
        }
    }

    private function resolveProvider(): EmbeddingProviderInterface
    {
        $active = $this->getActiveProvider();
        if (!isset($this->providers[$active])) {
            Log::warning("Configured embedding provider not found, falling back to voyage", ['provider' => $active]);
            return $this->providers['voyage'];
        }
        return $this->providers[$active];
    }

    public function embed(string $text, ?string $inputType = null): array
    {
        try {
            $provider = $this->resolveProvider();
            return $provider->embed($text, $inputType);
        } catch (\Throwable $e) {
            if ($this->getActiveProvider() !== 'voyage') {
                Log::warning("Active embedding provider failed, falling back to voyage", ['error' => $e->getMessage()]);
                return $this->providers['voyage']->embed($text, $inputType);
            }
            throw $e;
        }
    }

    public function embedBatch(array $texts, ?string $inputType = null): array
    {
        try {
            $provider = $this->resolveProvider();
            return $provider->embedBatch($texts, $inputType);
        } catch (\Throwable $e) {
            if ($this->getActiveProvider() !== 'voyage') {
                Log::warning("Active embedding provider batch failed, falling back to voyage", ['error' => $e->getMessage()]);
                return $this->providers['voyage']->embedBatch($texts, $inputType);
            }
            throw $e;
        }
    }

    public function getDimension(): int
    {
        $provider = $this->resolveProvider();
        return $provider->getDimension();
    }
}
