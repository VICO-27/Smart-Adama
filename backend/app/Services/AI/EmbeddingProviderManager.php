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
        $envDefault = config('ai.embedding_provider', 'gemini'); // gemini is default per requirements
        $setting = AdminSetting::where('key', 'ai_embedding_provider')->first();
        return $setting ? $setting->value : $envDefault;
    }

    private function resolveProvider(): EmbeddingProviderInterface
    {
        $active = $this->getActiveProvider();
        if (!isset($this->providers[$active])) {
            Log::warning("Configured embedding provider not found, falling back to gemini", ['provider' => $active]);
            return $this->providers['gemini'];
        }
        return $this->providers[$active];
    }

    public function embed(string $text, ?string $inputType = null): array
    {
        $provider = $this->resolveProvider();
        return $provider->embed($text, $inputType);
    }

    public function embedBatch(array $texts, ?string $inputType = null): array
    {
        $provider = $this->resolveProvider();
        return $provider->embedBatch($texts, $inputType);
    }

    public function getDimension(): int
    {
        $provider = $this->resolveProvider();
        return $provider->getDimension();
    }
}
