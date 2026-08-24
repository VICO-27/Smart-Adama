<?php

use App\Exceptions\AiProviderException;
use App\Services\AI\OllamaLLMGateway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('ai.ollama.llm_base_url', 'http://127.0.0.1:11434');
    Config::set('ai.ollama.llm_model', 'qwen3:4b');
});

it('can preserve thinking tags correctly from streamed response', function () {
    $mockBody = implode("\n", [
        json_encode(['message' => ['content' => '<think>']]),
        json_encode(['message' => ['content' => 'I am thinking...']]),
        json_encode(['message' => ['content' => '</think>']]),
        json_encode(['message' => ['content' => 'The answer is 42.']]),
        json_encode(['done' => true]),
    ]);

    Http::fake([
        '*' => Http::response($mockBody, 200, ['Content-Type' => 'application/x-ndjson']),
    ]);

    $gateway = new OllamaLLMGateway();

    $messages = [['role' => 'user', 'content' => 'What is the answer?']];
    $result = $gateway->chat($messages);

    expect($result)->toBe('<think>I am thinking...</think>The answer is 42.');
});

it('throws exception on connection failure', function () {
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Error Communicating with Server');
    });

    $gateway = new OllamaLLMGateway();
    $messages = [['role' => 'user', 'content' => 'Hello']];
    
    Config::set('ai.retry.times', 1);
    
    expect(fn() => $gateway->chat($messages))->toThrow(AiProviderException::class);
});
