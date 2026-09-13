<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqLLMGateway implements LLMGatewayInterface
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';

    private int $timeout;
    private int $connectTimeout;

    public function __construct()
    {
        $this->apiKey = config('ai.groq.api_key', env('GROQ_API_KEY', ''));
        $this->model = config('ai.groq.llm_model', env('GROQ_LLM_MODEL', ''));
        $this->timeout = (int) config('ai.groq.timeout', config('ai.llm_timeout', 6));
        $this->connectTimeout = (int) config('ai.groq.connect_timeout', config('ai.llm_connect_timeout', 4));
    }

    public function chat(array $messages, array $options = []): string
    {
        if (empty($this->apiKey)) {
            throw new AiProviderException("Groq API key is not configured.", "groq");
        }
        if (empty($this->model)) {
            throw new AiProviderException("Groq LLM model is not configured.", "groq");
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'reasoning_format' => 'hidden',
        ];

        $maxTokens = min((int) ($options['max_tokens'] ?? 600), 600);
        $payload['max_tokens'] = $maxTokens;
        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }

        $startTime = microtime(true);
        $httpOptions = [
            'connect_timeout' => $this->connectTimeout,
            'read_timeout' => $this->timeout,
        ];
        if (config('services.ai.force_ipv4', true)) {
            $httpOptions['curl'] = [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4];
        }

        try {
            $response = Http::withOptions($httpOptions)
                ->withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post($this->baseUrl, $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::warning("[PROVIDER_TIMEOUT] Groq chat connection/read timed out after {$latency}ms", [
                'provider' => 'groq',
                'model' => $this->model,
                'latency' => $latency,
                'error' => $e->getMessage(),
            ]);
            throw new AiProviderException("Groq connection timed out: " . $e->getMessage(), "groq");
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::error("[PROVIDER_TIMEOUT] Groq request failed after {$latency}ms", [
                'provider' => 'groq',
                'model' => $this->model,
                'latency' => $latency,
                'error' => $e->getMessage(),
            ]);
            throw new AiProviderException("Groq request failed: " . $e->getMessage(), "groq");
        }

        $latency = round((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();
            if ($status === 429) {
                Log::warning("[QUOTA_EXHAUSTED] Groq API quota/rate limit reached (429)", [
                    'provider' => 'groq',
                    'status' => $status,
                    'latency' => $latency,
                ]);
                throw new AiProviderException("Groq quota exhausted (HTTP 429).", "groq");
            }

            Log::error("Groq chat failed", [
                'status' => $status,
                'body' => $body,
                'latency' => $latency
            ]);
            throw new AiProviderException("Groq API request failed ({$status}): " . $body, "groq");
        }

        Log::info("Groq chat successful", [
            'model' => $this->model,
            'latency' => $latency,
        ]);

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? '';
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        if (empty($this->apiKey)) {
            throw new AiProviderException("Groq API key is not configured.", "groq");
        }
        if (empty($this->model)) {
            throw new AiProviderException("Groq LLM model is not configured.", "groq");
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => true,
            'reasoning_format' => 'hidden',
        ];

        $maxTokens = min((int) ($options['max_tokens'] ?? 600), 600);
        $payload['max_tokens'] = $maxTokens;
        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }

        $startTime = microtime(true);
        $httpOptions = [
            'stream' => true,
            'connect_timeout' => $this->connectTimeout,
            'read_timeout' => $this->timeout,
        ];
        if (config('services.ai.force_ipv4', true)) {
            $httpOptions['curl'] = [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->withOptions($httpOptions)
                ->timeout($this->timeout)
                ->post($this->baseUrl, $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::warning("[PROVIDER_TIMEOUT] Groq stream connection timed out after {$latency}ms", [
                'provider' => 'groq',
                'model' => $this->model,
                'latency' => $latency,
                'error' => $e->getMessage(),
            ]);
            throw new AiProviderException("Groq stream connection timed out.", "groq");
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::error("[PROVIDER_TIMEOUT] Groq stream dispatch failed after {$latency}ms", [
                'provider' => 'groq',
                'model' => $this->model,
                'latency' => $latency,
                'error' => $e->getMessage(),
            ]);
            throw new AiProviderException("Groq stream dispatch failed: " . $e->getMessage(), "groq");
        }

        $latency = round((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            $status = $response->status();
            if ($status === 429) {
                Log::warning("[QUOTA_EXHAUSTED] Groq streaming quota/rate limit reached (429)", [
                    'provider' => 'groq',
                    'status' => $status,
                    'latency' => $latency,
                ]);
                throw new AiProviderException("Groq streaming quota exhausted (HTTP 429).", "groq");
            }

            throw new AiProviderException("Groq API streaming request failed: {$status} - {$response->body()}", "groq");
        }

        $body = $response->toPsrResponse()->getBody();

        Log::info("Groq stream started", ['model' => $this->model, 'latency' => $latency]);

        $buffer = '';
        while (!$body->eof()) {
            try {
                $buffer .= $body->read(1024);
            } catch (\Throwable $e) {
                Log::warning("[STREAM_PARSE_ERROR] Groq stream body read error", [
                    'error' => $e->getMessage(),
                ]);
                throw new AiProviderException("Groq stream read failed: " . $e->getMessage(), "groq");
            }

            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                if (str_starts_with($line, 'data: ')) {
                    $jsonStr = substr($line, 6);
                    if (trim($jsonStr) === '[DONE]') continue;
                    try {
                        $data = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);
                        if ($data && isset($data['choices'][0]['delta']['content'])) {
                            yield $data['choices'][0]['delta']['content'];
                        }
                    } catch (\Throwable) {
                        Log::warning("[STREAM_PARSE_ERROR] Groq SSE JSON malformed", [
                            'snippet' => substr($jsonStr, 0, 80),
                        ]);
                    }
                }
            }
        }

        if (!empty(trim($buffer))) {
            $line = $buffer;
            if (str_starts_with($line, 'data: ')) {
                $jsonStr = substr($line, 6);
                if (trim($jsonStr) !== '[DONE]') {
                    try {
                        $data = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);
                        if ($data && isset($data['choices'][0]['delta']['content'])) {
                            yield $data['choices'][0]['delta']['content'];
                        }
                    } catch (\Throwable) {
                        Log::warning("[STREAM_PARSE_ERROR] Groq SSE JSON malformed in trailing buffer", [
                            'snippet' => substr($jsonStr, 0, 80),
                        ]);
                    }
                }
            }
        }
    }
}
