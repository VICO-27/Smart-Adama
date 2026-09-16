<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\Contracts\LLMGatewayInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiLLMGateway implements LLMGatewayInterface
{
    private string $apiKey;

    private string $model;

    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    private int $timeout;

    private int $connectTimeout;

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->model = config('ai.gemini.llm_model', env('GEMINI_LLM_MODEL', 'gemini-3.5-flash-lite'));
        $this->timeout = (int) config('ai.gemini.timeout', config('ai.llm_timeout', 6));
        $this->connectTimeout = (int) config('ai.gemini.connect_timeout', config('ai.llm_connect_timeout', 4));
    }

    private function getModelName(): string
    {
        // Remove 'models/' prefix if present
        $model = str_replace('models/', '', $this->model);

        return $model;
    }

    public function chat(array $messages, array $options = []): string
    {
        if (empty($this->apiKey)) {
            throw new AiProviderException('Gemini API key is not configured.', 'gemini');
        }

        $formattedMessages = $this->formatMessages($messages);
        $systemInstruction = $this->extractSystemInstruction($messages);

        $payload = [
            'contents' => $formattedMessages,
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        if (isset($options['max_tokens'])) {
            $payload['generationConfig']['maxOutputTokens'] = $options['max_tokens'];
        }
        if (isset($options['temperature'])) {
            $payload['generationConfig']['temperature'] = $options['temperature'];
        }

        // Optimize latency by setting thinking level to low
        $payload['generationConfig']['thinkingConfig'] = [
            'thinkingLevel' => 'low',
        ];

        $modelName = $this->getModelName();
        $url = "{$this->baseUrl}/{$modelName}:generateContent?key={$this->apiKey}";

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
                ->timeout($this->timeout)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::warning("[PROVIDER_TIMEOUT] Gemini chat connection/read timed out after {$latency}ms", [
                'provider' => 'gemini',
                'model' => $modelName,
                'latency' => $latency,
                'error' => $e->getMessage(),
            ]);
            throw new AiProviderException('Gemini connection timed out: '.$e->getMessage(), 'gemini');
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::error("[PROVIDER_TIMEOUT] Gemini request threw unexpected exception after {$latency}ms", [
                'provider' => 'gemini',
                'model' => $modelName,
                'latency' => $latency,
                'error' => $e->getMessage(),
            ]);
            throw new AiProviderException('Gemini request failed: '.$e->getMessage(), 'gemini');
        }

        $latency = round((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();
            if ($status === 429) {
                Log::warning('[QUOTA_EXHAUSTED] Gemini API quota/rate limit reached (429)', [
                    'provider' => 'gemini',
                    'status' => $status,
                    'latency' => $latency,
                ]);
                throw new AiProviderException('Gemini quota exhausted (HTTP 429).', 'gemini');
            }

            Log::error('Gemini chat failed', [
                'status' => $status,
                'body' => $body,
                'latency' => $latency,
            ]);
            throw new AiProviderException("Gemini API request failed ({$status}): ".$body, 'gemini');
        }

        $data = $response->json();

        Log::info('Gemini chat successful', [
            'model' => $modelName,
            'latency' => $latency,
        ]);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        if (empty($this->apiKey)) {
            throw new AiProviderException('Gemini API key is not configured.', 'gemini');
        }

        $formattedMessages = $this->formatMessages($messages);
        $systemInstruction = $this->extractSystemInstruction($messages);

        $payload = [
            'contents' => $formattedMessages,
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        if (isset($options['max_tokens'])) {
            $payload['generationConfig']['maxOutputTokens'] = $options['max_tokens'];
        }
        if (isset($options['temperature'])) {
            $payload['generationConfig']['temperature'] = $options['temperature'];
        }

        // Optimize streaming TTFT by minimizing internal reasoning tokens
        $payload['generationConfig']['thinkingConfig'] = [
            'thinkingLevel' => 'low',
        ];

        $modelName = $this->getModelName();
        $url = "{$this->baseUrl}/{$modelName}:streamGenerateContent?key={$this->apiKey}&alt=sse";

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
            $response = Http::withOptions($httpOptions)
                ->timeout($this->timeout)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::warning("[PROVIDER_TIMEOUT] Gemini stream connect/handshake timed out after {$latency}ms", [
                'provider' => 'gemini',
                'model' => $modelName,
                'latency' => $latency,
                'error' => $e->getMessage(),
            ]);
            throw new AiProviderException('Gemini stream connection timed out.', 'gemini');
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::error("[PROVIDER_TIMEOUT] Gemini stream dispatch failed after {$latency}ms", [
                'provider' => 'gemini',
                'model' => $modelName,
                'latency' => $latency,
                'error' => $e->getMessage(),
            ]);
            throw new AiProviderException('Gemini stream dispatch failed: '.$e->getMessage(), 'gemini');
        }

        $latency = round((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            $status = $response->status();
            if ($status === 429) {
                Log::warning('[QUOTA_EXHAUSTED] Gemini streaming quota/rate limit reached (429)', [
                    'provider' => 'gemini',
                    'status' => $status,
                    'latency' => $latency,
                ]);
                throw new AiProviderException('Gemini streaming quota exhausted (HTTP 429).', 'gemini');
            }

            Log::error('Gemini stream failed', ['status' => $status, 'body' => (string) $response->getBody()]);
            throw new AiProviderException("Gemini API streaming request failed ({$status}).", 'gemini');
        }

        $body = $response->toPsrResponse()->getBody();

        Log::info('Gemini stream started', ['model' => $modelName, 'latency' => $latency]);

        $buffer = '';
        while (! $body->eof()) {
            try {
                $buffer .= $body->read(1024);
            } catch (\Throwable $e) {
                Log::warning('[STREAM_PARSE_ERROR] Gemini stream body read error', [
                    'error' => $e->getMessage(),
                ]);
                throw new AiProviderException('Gemini stream read failed: '.$e->getMessage(), 'gemini');
            }

            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                if (str_starts_with($line, 'data: ')) {
                    $jsonStr = substr($line, 6);
                    if (trim($jsonStr) === '[DONE]') {
                        continue;
                    }
                    try {
                        $data = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);
                        if ($data && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                            yield $data['candidates'][0]['content']['parts'][0]['text'];
                        }
                    } catch (\Throwable) {
                        Log::warning('[STREAM_PARSE_ERROR] Gemini SSE JSON malformed', [
                            'snippet' => substr($jsonStr, 0, 80),
                        ]);
                    }
                }
            }
        }

        if (! empty(trim($buffer))) {
            $line = $buffer;
            if (str_starts_with($line, 'data: ')) {
                $jsonStr = substr($line, 6);
                if (trim($jsonStr) !== '[DONE]') {
                    try {
                        $data = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);
                        if ($data && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                            yield $data['candidates'][0]['content']['parts'][0]['text'];
                        }
                    } catch (\Throwable) {
                        Log::warning('[STREAM_PARSE_ERROR] Gemini SSE JSON malformed in trailing buffer', [
                            'snippet' => substr($jsonStr, 0, 80),
                        ]);
                    }
                }
            }
        }
    }

    private function extractSystemInstruction(array &$messages): ?string
    {
        $systemText = null;
        if (! empty($messages) && $messages[0]['role'] === 'system') {
            $systemText = $messages[0]['content'];
            array_shift($messages); // Remove system message from main context
        }

        return $systemText;
    }

    private function formatMessages(array $messages): array
    {
        $formatted = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $formatted[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]],
            ];
        }

        return $formatted;
    }
}
