<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Models\AdminSetting;
use App\Services\AI\Contracts\LLMGatewayInterface;
use Illuminate\Support\Facades\Log;

class LLMProviderManager implements LLMGatewayInterface
{
    private array $gateways = [];

    public function __construct(
        private GeminiLLMGateway $gemini,
        private GroqLLMGateway $groq,
        private OllamaLLMGateway $ollama
    ) {
        $this->gateways = [
            'gemini' => $gemini,
            'groq' => $groq,
            'ollama' => $ollama,
        ];
    }

    private function getActiveProvider(): string
    {
        // Provider configuration priority: Environment -> Admin -> Active
        // Default to env, override by DB setting.
        $envDefault = config('ai.llm_provider', 'gemini');
        try {
            $setting = AdminSetting::where('key', 'ai_llm_provider')->first();

            return $setting ? $setting->value : $envDefault;
        } catch (\Throwable) {
            return $envDefault;
        }
    }

    public function chat(array $messages, array $options = []): string
    {
        $activeProvider = $this->getActiveProvider();
        $fallbackSequence = $this->getFallbackSequence($activeProvider);
        $totalStartTime = microtime(true);

        foreach ($fallbackSequence as $index => $providerName) {
            if (! isset($this->gateways[$providerName])) {
                continue;
            }

            $providerStartTime = microtime(true);
            try {
                Log::info('Attempting LLM provider for chat', [
                    'provider' => $providerName,
                    'is_fallback' => $index > 0,
                ]);

                $response = $this->gateways[$providerName]->chat($messages, $options);
                $providerElapsed = round((microtime(true) - $providerStartTime) * 1000);
                $totalElapsed = round((microtime(true) - $totalStartTime) * 1000);

                Log::info('[AI_TIMING] LLM chat completed', [
                    'provider' => $providerName,
                    'provider_elapsed_ms' => $providerElapsed,
                    'total_elapsed_ms' => $totalElapsed,
                ]);

                // Strip <think> and chain-of-thought blocks (Req: do not expose internal reasoning to frontend or DB)
                $cleaned = ReasoningFilter::strip($response);
                if (trim($cleaned) === '') {
                    throw new \RuntimeException("Provider {$providerName} returned empty response after stripping internal reasoning.");
                }

                return $cleaned;
            } catch (\Exception $e) {
                $providerElapsed = round((microtime(true) - $providerStartTime) * 1000);
                $isLast = ($providerName === end($fallbackSequence));

                if (! $isLast) {
                    Log::warning("[PROVIDER_FALLBACK] Provider {$providerName} failed for chat after {$providerElapsed}ms, falling back immediately", [
                        'provider' => $providerName,
                        'elapsed_ms' => $providerElapsed,
                        'error' => $e->getMessage(),
                    ]);
                } else {
                    Log::error('All LLM providers failed for chat', [
                        'last_provider' => $providerName,
                        'elapsed_ms' => $providerElapsed,
                        'error' => $e->getMessage(),
                    ]);
                    throw new AiProviderException('All LLM providers failed. Last error: '.$e->getMessage(), $activeProvider);
                }
            }
        }

        throw new AiProviderException('No LLM provider available.', 'system');
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        $activeProvider = $this->getActiveProvider();
        $fallbackSequence = $this->getFallbackSequence($activeProvider);
        $totalStartTime = microtime(true);

        foreach ($fallbackSequence as $index => $providerName) {
            if (! isset($this->gateways[$providerName])) {
                continue;
            }

            $providerStartTime = microtime(true);
            $hasYieldedTokens = false;
            $firstTokenLogged = false;

            try {
                Log::info('Attempting LLM provider for stream', [
                    'provider' => $providerName,
                    'is_fallback' => $index > 0,
                ]);

                $generator = $this->gateways[$providerName]->streamChat($messages, $options);
                $generator->rewind();

                if ($generator->valid()) {
                    $inThinkBlock = false;
                    $buffer = '';

                    do {
                        $chunk = $generator->current();
                        $buffer .= $chunk;

                        // Process the buffer
                        while (strlen($buffer) > 0) {
                            if (! $inThinkBlock) {
                                $thinkPos = strpos($buffer, '<think>');
                                if ($thinkPos !== false) {
                                    // Yield everything before <think>
                                    $yieldPart = substr($buffer, 0, $thinkPos);
                                    if ($yieldPart !== '') {
                                        if (! $firstTokenLogged) {
                                            $firstTokenLogged = true;
                                            $ttft = round((microtime(true) - $totalStartTime) * 1000);
                                            Log::info('[AI_TIMING] TTFT (first token) reached', [
                                                'provider' => $providerName,
                                                'ttft_ms' => $ttft,
                                            ]);
                                        }
                                        $hasYieldedTokens = true;
                                        yield $yieldPart;
                                    }

                                    $buffer = substr($buffer, $thinkPos + 7);
                                    $inThinkBlock = true;
                                } else {
                                    // No <think> found. We must keep the last 6 chars in case they are part of '<think>'
                                    if (strlen($buffer) < 7) {
                                        break; // Wait for more data
                                    }
                                    $yieldPart = substr($buffer, 0, -6);
                                    if (! $firstTokenLogged) {
                                        $firstTokenLogged = true;
                                        $ttft = round((microtime(true) - $totalStartTime) * 1000);
                                        Log::info('[AI_TIMING] TTFT (first token) reached', [
                                            'provider' => $providerName,
                                            'ttft_ms' => $ttft,
                                        ]);
                                    }
                                    $hasYieldedTokens = true;
                                    yield $yieldPart;
                                    $buffer = substr($buffer, -6);
                                    break;
                                }
                            } else {
                                $endThinkPos = strpos($buffer, '</think>');
                                if ($endThinkPos !== false) {
                                    $buffer = substr($buffer, $endThinkPos + 8);
                                    $buffer = ltrim($buffer); // Remove trailing newlines right after think block
                                    $inThinkBlock = false;
                                } else {
                                    // No </think> found, discard all but last 7 chars
                                    if (strlen($buffer) > 7) {
                                        $buffer = substr($buffer, -7);
                                    }
                                    break;
                                }
                            }
                        }

                        $generator->next();
                    } while ($generator->valid());

                    // Flush remaining buffer
                    if (! $inThinkBlock && $buffer !== '') {
                        if (! $firstTokenLogged) {
                            $firstTokenLogged = true;
                            $ttft = round((microtime(true) - $totalStartTime) * 1000);
                            Log::info('[AI_TIMING] TTFT (first token) reached', [
                                'provider' => $providerName,
                                'ttft_ms' => $ttft,
                            ]);
                        }
                        $hasYieldedTokens = true;
                        yield $buffer;
                    }

                    $totalStreamTime = round((microtime(true) - $totalStartTime) * 1000);
                    Log::info('[AI_TIMING] LLM stream finished successfully', [
                        'provider' => $providerName,
                        'total_ms' => $totalStreamTime,
                    ]);

                    return; // Success
                }

                return; // Empty stream
            } catch (\Exception $e) {
                $elapsed = round((microtime(true) - $providerStartTime) * 1000);

                if ($hasYieldedTokens) {
                    // Flush any remaining characters in the buffer so that partial output is 100% complete
                    if (! $inThinkBlock && $buffer !== '') {
                        yield $buffer;
                        $buffer = '';
                    }

                    // Tokens have already been emitted to the client. Falling back now would restart
                    // the output from the beginning, corrupting the message.
                    Log::error("[STREAM_PARSE_ERROR] LLM provider {$providerName} stream failed mid-stream after emitting tokens", [
                        'provider' => $providerName,
                        'elapsed_ms' => $elapsed,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                $isLast = ($providerName === end($fallbackSequence));
                if (! $isLast) {
                    Log::warning("[PROVIDER_FALLBACK] Provider {$providerName} failed before yielding tokens after {$elapsed}ms, falling back immediately", [
                        'failed_provider' => $providerName,
                        'elapsed_ms' => $elapsed,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }

                Log::error('All LLM providers failed for streaming', [
                    'last_provider' => $providerName,
                    'elapsed_ms' => $elapsed,
                    'error' => $e->getMessage(),
                ]);
                throw new AiProviderException('All LLM providers failed. Last error: '.$e->getMessage(), $activeProvider);
            }
        }

        throw new AiProviderException('No LLM provider available for streaming.', 'system');
    }

    private function getFallbackSequence(string $activeProvider): array
    {
        $sequence = [$activeProvider];

        if ($activeProvider === 'gemini') {
            $sequence[] = 'groq';
            // Do NOT automatically fallback to Ollama in production (Req 7)
        } elseif ($activeProvider === 'groq') {
            // Groq can fallback to Gemini
            $sequence[] = 'gemini';
        }
        // If ollama is primary, no automatic fallback to cloud providers to preserve local privacy intent

        return array_unique($sequence);
    }
}
