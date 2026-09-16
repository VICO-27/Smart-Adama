<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoyageEmbeddingProvider implements EmbeddingProviderInterface
{
    private string $apiKey;

    private string $model;

    private int $dimension;

    private string $baseUrl = 'https://api.voyageai.com/v1/embeddings';

    private int $timeout;

    private int $connectTimeout;

    public function __construct()
    {
        $this->apiKey = config('ai.voyage.api_key', env('VOYAGE_API_KEY', ''));
        $this->model = config('ai.voyage.embedding_model', env('VOYAGE_EMBEDDING_MODEL', 'voyage-large-2-instruct'));
        $this->dimension = (int) config('ai.voyage.embedding_dimension', env('VOYAGE_EMBEDDING_DIMENSION', 1024));
        $this->timeout = (int) config('ai.voyage.timeout', config('ai.embedding_timeout', 6));
        $this->connectTimeout = (int) config('ai.voyage.connect_timeout', config('ai.embedding_connect_timeout', 3));
    }

    public function embed(string $text, ?string $inputType = null): array
    {
        return $this->embedBatch([$text], $inputType)[0];
    }

    public function embedBatch(array $texts, ?string $inputType = null): array
    {
        if (empty($this->apiKey)) {
            Log::error('[EMBEDDING_ERROR] Voyage API key is not configured');
            throw new AiProviderException('Voyage API key is not configured.', 'voyage');
        }

        $payload = [
            'input' => $texts,
            'model' => $this->model,
        ];

        if ($inputType !== null) {
            $payload['input_type'] = $inputType;
        }

        $startTime = microtime(true);
        $options = [
            'connect_timeout' => $this->connectTimeout,
        ];
        if (config('services.ai.force_ipv4', true)) {
            $options['curl'] = [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4];
        }

        try {
            $response = Http::withOptions($options)
                ->withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post($this->baseUrl, $payload);
        } catch (ConnectionException $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::error("[PROVIDER_TIMEOUT] [EMBEDDING_ERROR] Voyage embedding timed out after {$latency}ms", [
                'provider' => 'voyage',
                'latency' => $latency,
                'error' => $e->getMessage(),
            ]);
            throw new AiProviderException('Voyage embedding connection timed out: '.$e->getMessage(), 'voyage');
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::error("[EMBEDDING_ERROR] Voyage embedding dispatch failed after {$latency}ms", [
                'provider' => 'voyage',
                'latency' => $latency,
                'error' => $e->getMessage(),
            ]);
            throw new AiProviderException('Voyage embedding failed: '.$e->getMessage(), 'voyage');
        }

        $latency = round((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();
            if ($status === 429) {
                Log::warning('[QUOTA_EXHAUSTED] [EMBEDDING_ERROR] Voyage embedding quota/rate limit reached (429)', [
                    'provider' => 'voyage',
                    'status' => $status,
                    'latency' => $latency,
                ]);
                throw new AiProviderException('Voyage embedding quota exhausted (HTTP 429).', 'voyage');
            }

            Log::error('[EMBEDDING_ERROR] Voyage batch embedding failed', [
                'status' => $status,
                'body' => $body,
                'latency' => $latency,
            ]);
            throw new AiProviderException("Voyage embedding request failed ({$status}): ".$body, 'voyage');
        }

        $data = $response->json();

        $embeddings = [];
        if (isset($data['data'])) {
            // Ensure they are sorted by index
            usort($data['data'], fn ($a, $b) => $a['index'] <=> $b['index']);
            foreach ($data['data'] as $item) {
                $embeddings[] = $item['embedding'];
            }
        }

        if (count($embeddings) !== count($texts)) {
            throw new AiProviderException('Voyage returned '.count($embeddings).' embeddings for '.count($texts).' texts.', 'voyage');
        }

        return $embeddings;
    }

    public function getDimension(): int
    {
        return $this->dimension;
    }
}
