<?php

use App\Exceptions\AiProviderException;
use App\Services\AI\OllamaEmbeddingProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('ai.ollama.embedding_base_url', 'http://127.0.0.1:11434');
    Config::set('ai.ollama.embedding_model', 'qwen3-embedding:0.6b');
    Config::set('ai.ollama.embedding_dimension', 1024);
    Config::set('ai.retry.times', 1);
});

it('can fetch embeddings successfully', function () {
    $mockData = [
        'embeddings' => [
            array_fill(0, 1024, 0.5)
        ]
    ];

    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode($mockData)),
    ]);
    
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OllamaEmbeddingProvider();
    $reflection = new \ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $result = $provider->embed('test string');

    expect($result)->toBeArray();
    expect(count($result))->toBe(1024);
});

it('throws exception if dimension mismatch', function () {
    $mockData = [
        'embeddings' => [
            array_fill(0, 768, 0.5) // Wrong dimension
        ]
    ];

    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode($mockData)),
    ]);
    
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OllamaEmbeddingProvider();
    $reflection = new \ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    expect(fn() => $provider->embed('test string'))->toThrow(AiProviderException::class, 'Embedding dimension mismatch. Expected 1024, got 768');
});
