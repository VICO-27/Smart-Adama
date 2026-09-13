<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class OllamaEmbeddingProvider implements EmbeddingProviderInterface
{
    private Client $client;
    private string $model;
    private int $dimension;

    public function __construct()
    {
        $baseUrl = rtrim(config('ai.ollama.embedding_base_url', 'http://127.0.0.1:11434'), '/');

        $this->client = new Client([
            'base_uri' => $baseUrl . '/',
            'timeout'  => 600,
            'connect_timeout' => 30,
            'headers'  => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ]);

        $this->model     = config('ai.ollama.embedding_model', 'qwen3-embedding:0.6b');
        $this->dimension = (int) config('ai.ollama.embedding_dimension', 1024);
    }

    public function embed(string $text, ?string $inputType = null): array
    {
        return $this->embedBatch([$text])[0];
    }

    public function embedBatch(array $texts, ?string $inputType = null): array
    {
        if (empty($texts)) {
            return [];
        }

        $attempts = config('ai.retry.times', 3);
        $backoff  = config('ai.retry.backoff', [500, 1000, 2000]);

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                $response = $this->client->post('api/embed', [
                    'json' => [
                        'model' => $this->model,
                        'input' => $texts,
                        'keep_alive' => -1,
                    ],
                ]);

                $body = json_decode((string) $response->getBody(), true);

                if (!isset($body['embeddings']) || !is_array($body['embeddings'])) {
                    throw new AiProviderException("Invalid response from Ollama embedding API.");
                }

                $embeddings = $body['embeddings'];

                // Validate dimension
                foreach ($embeddings as $idx => $vector) {
                    if (count($vector) !== $this->dimension) {
                        Log::error('OllamaEmbeddingProvider: Dimension mismatch', [
                            'expected' => $this->dimension,
                            'actual'   => count($vector),
                            'index'    => $idx,
                        ]);
                        throw new AiProviderException("Embedding dimension mismatch. Expected {$this->dimension}, got " . count($vector), 'ollama');
                    }
                }

                return $embeddings;

            } catch (ServerException $e) {
                if ($attempt < $attempts - 1) {
                    usleep(($backoff[$attempt] ?? 2000) * 1000);
                    continue;
                }
                throw new AiProviderException('Ollama Embedding API unavailable: ' . $e->getMessage(), 'ollama', $e);
            } catch (ConnectException $e) {
                if ($attempt < $attempts - 1) {
                    usleep(($backoff[$attempt] ?? 2000) * 1000);
                    continue;
                }
                throw new AiProviderException('Ollama Embedding connection failed. Is it running?', 'ollama', $e);
            } catch (\Throwable $e) {
                throw new AiProviderException('Ollama Embedding API error: ' . $e->getMessage(), 'ollama', $e);
            }
        }

        return [];
    }

    public function getDimension(): int
    {
        return $this->dimension;
    }
}
