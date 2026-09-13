<?php

namespace Tests\Unit\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\GeminiLLMGateway;
use App\Services\AI\GroqLLMGateway;
use App\Services\AI\LLMProviderManager;
use App\Services\AI\OllamaLLMGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'ai.llm_provider' => 'gemini',
        'ai.gemini.api_key' => 'test-gemini-key',
        'ai.gemini.llm_model' => 'gemini-3.7-flash',
        'ai.gemini.timeout' => 2,
        'ai.gemini.connect_timeout' => 1,
        'ai.groq.api_key' => 'test-groq-key',
        'ai.groq.llm_model' => 'llama-3.3-70b-versatile',
        'ai.groq.timeout' => 2,
        'ai.groq.connect_timeout' => 1,
    ]);
});

// ── Gemini Gateway Tests ──────────────────────────────────────────────────

it('throws AiProviderException when Gemini connection times out in chat', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => function () {
            throw new ConnectionException("Connection timed out after 1000ms");
        },
    ]);

    Log::shouldReceive('warning')
        ->withArgs(function ($message) {
            return str_contains($message, '[PROVIDER_TIMEOUT]');
        })
        ->atLeast()->once();

    $gateway = new GeminiLLMGateway();
    $gateway->chat([['role' => 'user', 'content' => 'Hello']]);
})->throws(AiProviderException::class);

it('throws AiProviderException when Gemini returns 429 quota exhausted', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['code' => 429, 'message' => 'Resource has been exhausted (e.g. check quota).']
        ], 429),
    ]);

    Log::shouldReceive('warning')
        ->withArgs(function ($message) {
            return str_contains($message, '[QUOTA_EXHAUSTED]');
        })
        ->atLeast()->once();

    $gateway = new GeminiLLMGateway();
    $gateway->chat([['role' => 'user', 'content' => 'Hello']]);
})->throws(AiProviderException::class, 'Gemini quota exhausted');

// ── Groq Gateway Tests ────────────────────────────────────────────────────

it('throws AiProviderException when Groq connection times out in chat', function () {
    Http::fake([
        'api.groq.com/*' => function () {
            throw new ConnectionException("Connection timed out after 1000ms");
        },
    ]);

    Log::shouldReceive('warning')
        ->withArgs(function ($message) {
            return str_contains($message, '[PROVIDER_TIMEOUT]');
        })
        ->atLeast()->once();

    $gateway = new GroqLLMGateway();
    $gateway->chat([['role' => 'user', 'content' => 'Hello']]);
})->throws(AiProviderException::class);

it('throws AiProviderException when Groq returns 429 rate limit', function () {
    Http::fake([
        'api.groq.com/*' => Http::response([
            'error' => ['code' => 429, 'message' => 'Rate limit reached']
        ], 429),
    ]);

    Log::shouldReceive('warning')
        ->withArgs(function ($message) {
            return str_contains($message, '[QUOTA_EXHAUSTED]');
        })
        ->atLeast()->once();

    $gateway = new GroqLLMGateway();
    $gateway->chat([['role' => 'user', 'content' => 'Hello']]);
})->throws(AiProviderException::class, 'Groq quota exhausted');

// ── LLMProviderManager Fallback Tests ─────────────────────────────────────

it('falls back from Gemini to Groq for chat when Gemini fails', function () {
    $geminiMock = \Mockery::mock(GeminiLLMGateway::class);
    $groqMock = \Mockery::mock(GroqLLMGateway::class);
    $ollamaMock = \Mockery::mock(OllamaLLMGateway::class);

    $geminiMock->shouldReceive('chat')
        ->once()
        ->andThrow(new AiProviderException('Gemini timeout', 'gemini'));

    $groqMock->shouldReceive('chat')
        ->once()
        ->andReturn('Hello from Groq fallback');

    $manager = new LLMProviderManager($geminiMock, $groqMock, $ollamaMock);

    $result = $manager->chat([['role' => 'user', 'content' => 'Hi']]);
    expect($result)->toBe('Hello from Groq fallback');
});

it('falls back from Gemini to Groq for streaming when Gemini fails before first token', function () {
    $geminiMock = \Mockery::mock(GeminiLLMGateway::class);
    $groqMock = \Mockery::mock(GroqLLMGateway::class);
    $ollamaMock = \Mockery::mock(OllamaLLMGateway::class);

    $geminiMock->shouldReceive('streamChat')
        ->once()
        ->andReturnUsing(function () {
            throw new AiProviderException('Gemini stream connection timed out.', 'gemini');
        });

    $groqMock->shouldReceive('streamChat')
        ->once()
        ->andReturnUsing(function () {
            yield 'Groq ';
            yield 'streaming ';
            yield 'tokens';
        });

    $manager = new LLMProviderManager($geminiMock, $groqMock, $ollamaMock);

    $tokens = [];
    foreach ($manager->streamChat([['role' => 'user', 'content' => 'Hi']]) as $token) {
        $tokens[] = $token;
    }

    expect(implode('', $tokens))->toBe('Groq streaming tokens');
});

it('does not fallback to Groq if Gemini fails mid-stream after tokens were emitted', function () {
    $geminiMock = \Mockery::mock(GeminiLLMGateway::class);
    $groqMock = \Mockery::mock(GroqLLMGateway::class);
    $ollamaMock = \Mockery::mock(OllamaLLMGateway::class);

    $geminiMock->shouldReceive('streamChat')
        ->once()
        ->andReturnUsing(function () {
            yield 'Part 1 of Gemini response ';
            throw new AiProviderException('Gemini network dropped mid-stream', 'gemini');
        });

    // Groq must NOT be called
    $groqMock->shouldNotReceive('streamChat');

    $manager = new LLMProviderManager($geminiMock, $groqMock, $ollamaMock);

    $tokens = [];
    try {
        foreach ($manager->streamChat([['role' => 'user', 'content' => 'Hi']]) as $token) {
            $tokens[] = $token;
        }
        $this->fail('Expected exception was not thrown');
    } catch (\Throwable $e) {
        expect($e->getMessage())->toContain('Gemini network dropped mid-stream');
        expect(implode('', $tokens))->toBe('Part 1 of Gemini response ');
    }
});

it('falls back to next provider when current provider returns empty response or stripped reasoning in chat', function () {
    $geminiMock = \Mockery::mock(GeminiLLMGateway::class);
    $groqMock = \Mockery::mock(GroqLLMGateway::class);
    $ollamaMock = \Mockery::mock(OllamaLLMGateway::class);

    // Gemini returns only unclosed think block that strips to empty
    $geminiMock->shouldReceive('chat')
        ->once()
        ->andReturn("<think>Analyzing user question without closing tag");

    // Groq successfully provides a cleaned response
    $groqMock->shouldReceive('chat')
        ->once()
        ->andReturn("Here is the answer from Groq.");

    $manager = new LLMProviderManager($geminiMock, $groqMock, $ollamaMock);

    $result = $manager->chat([['role' => 'user', 'content' => 'how take quizes']]);
    expect($result)->toBe("Here is the answer from Groq.");
});
