<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\Contracts\LLMGatewayInterface;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;

class OllamaLLMGateway implements LLMGatewayInterface
{
    private string $model;

    private int $maxTokens;

    public function __construct()
    {
        $this->model = config('ai.ollama.llm_model', 'qwen2.5:0.5b');
        $this->maxTokens = (int) config('ai.ollama.max_tokens', 120);
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        $baseUrl = rtrim(config('ai.ollama.llm_base_url', 'http://127.0.0.1:11434'), '/');

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'stream' => true,
            'keep_alive' => -1,
            'options' => [
                'num_thread' => 4,
                'num_predict' => 120,
                'temperature' => 0.1,
                'top_p' => $options['top_p'] ?? 0.9,
                'think' => false,
            ],
        ];

        $attempts = config('ai.retry.times', 3);
        $backoff = config('ai.retry.backoff', [500, 1000, 2000]);

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                $response = Http::retry(3, 500, throw: false)
                    ->withOptions([
                        'stream' => true,
                        'timeout' => 120,
                        'connect_timeout' => 15,
                    ])
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/x-ndjson',
                    ])
                    ->post($baseUrl.'/api/chat', $payload);

                if (! $response->successful()) {
                    throw new \Exception('Ollama API returned status '.$response->status());
                }

                $stream = $response->toPsrResponse()->getBody();

                while (! $stream->eof()) {
                    $line = Utils::readLine($stream);
                    $line = trim($line);

                    if (empty($line)) {
                        continue;
                    }

                    $data = json_decode($line, true);
                    if (! $data || ! isset($data['message']['content'])) {
                        continue;
                    }

                    $token = $data['message']['content'];
                    if ($token !== '') {
                        yield $token;
                    }

                    if ($data['done'] ?? false) {
                        return;
                    }
                }

                return;

            } catch (\Throwable $e) {
                if ($attempt < $attempts - 1) {
                    usleep(($backoff[$attempt] ?? 2000) * 1000);

                    continue;
                }
                throw new AiProviderException('Ollama API error: '.$e->getMessage(), 'ollama', $e);
            }
        }
    }

    public function chat(array $messages, array $options = []): string
    {
        $full = '';
        foreach ($this->streamChat($messages, $options) as $token) {
            $full .= $token;
        }

        return $full;
    }
}
