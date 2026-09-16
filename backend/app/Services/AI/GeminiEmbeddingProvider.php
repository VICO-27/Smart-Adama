<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiEmbeddingProvider implements EmbeddingProviderInterface
{
    private string $apiKey;

    private string $model;

    private int $dimension;

    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->model = config('ai.gemini.embedding_model', env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'));
        $this->dimension = (int) config('ai.gemini.embedding_dimension', env('GEMINI_EMBEDDING_DIMENSION', 1024));
    }

    private function getModelName(): string
    {
        return str_replace('models/', '', $this->model);
    }

    public function embed(string $text, ?string $inputType = null): array
    {
        return $this->embedBatch([$text])[0];
    }

    public function embedBatch(array $texts, ?string $inputType = null): array
    {
        if (empty($this->apiKey)) {
            throw new AiProviderException('Gemini API key is not configured.', 'gemini');
        }

        $modelName = $this->getModelName();
        $url = "{$this->baseUrl}/{$modelName}:batchEmbedContents?key={$this->apiKey}";

        $requests = [];
        foreach ($texts as $text) {
            $requests[] = [
                'model' => 'models/'.$modelName,
                'content' => [
                    'parts' => [['text' => $text]],
                ],
                'outputDimensionality' => $this->dimension,
            ];
        }

        $payload = ['requests' => $requests];

        $startTime = microtime(true);
        $response = Http::timeout(60)->post($url, $payload);
        $latency = round((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            Log::error('Gemini batch embedding failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'latency' => $latency,
            ]);
            throw new AiProviderException('Gemini embedding request failed: '.$response->body(), 'gemini');
        }

        $data = $response->json();

        $embeddings = [];
        if (isset($data['embeddings'])) {
            foreach ($data['embeddings'] as $emb) {
                $embeddings[] = $emb['values'];
            }
        }

        if (count($embeddings) !== count($texts)) {
            throw new AiProviderException('Gemini returned '.count($embeddings).' embeddings for '.count($texts).' texts.', 'gemini');
        }

        return $embeddings;
    }

    public function getDimension(): int
    {
        return $this->dimension;
    }
}
