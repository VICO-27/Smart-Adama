<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Models\ChatSession;
use App\Services\Chat\ChatOrchestrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatMessageController extends Controller
{
    public function __construct(
        private readonly ChatOrchestrator $orchestrator,
    ) {
    }

    public function store(SendMessageRequest $request, ChatSession $session): StreamedResponse
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        $userContent = $request->content;

        // 1. Persist user message
        $userMessage = $session->messages()->create([
            'role'    => 'user',
            'content' => $userContent,
        ]);

        // Auto-title session from first message
        if ($session->title === 'New Chat') {
            $session->update([
                'title'            => mb_substr($userContent, 0, 60),
                'last_activity_at' => now(),
            ]);
        } else {
            $session->update(['last_activity_at' => now()]);
        }

        // Create the assistant message placeholder before streaming starts
        $assistantMessage = null;
        DB::transaction(function () use ($session, &$assistantMessage) {
            $assistantMessage = $session->messages()->create([
                'role'    => 'assistant',
                'content' => '',
            ]);
        });

        return new StreamedResponse(function () use ($session, $userContent, $userMessage, $assistantMessage) {
            ignore_user_abort(true);
            ob_implicit_flush(1);
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $emitActivity = function (string $stage, string $message) {
                try {
                    $json = json_encode(['stage' => $stage, 'message' => $message], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                    echo 'event: activity' . "\n";
                    echo 'data: ' . $json . "\n\n";
                    @ob_flush();
                    flush();
                } catch (\Throwable $e) {
                    Log::warning('SSE activity JSON encoding failed', ['error' => $e->getMessage()]);
                }
            };
            
            $emitToken = function (string $token) {
                try {
                    $json = json_encode(['content' => $token], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                    echo 'event: token' . "\n";
                    echo 'data: ' . $json . "\n\n";
                    @ob_flush();
                    flush();
                } catch (\Throwable $e) {
                    Log::warning('SSE token JSON encoding failed', ['error' => $e->getMessage()]);
                }
            };
            
            $emitError = function (string $code, string $message) {
                try {
                    $json = json_encode(['error' => ['code' => $code, 'message' => $message]], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                    echo 'event: error' . "\n";
                    echo 'data: ' . $json . "\n\n";
                    @ob_flush();
                    flush();
                } catch (\Throwable $e) {
                    Log::error('SSE error JSON encoding failed', ['error' => $e->getMessage()]);
                }
            };
            
            $emitComplete = function (int $messageId, bool $grounded, array $citations, string $finalHtml) {
                try {
                    $json = json_encode([
                        'message_id'   => $messageId,
                        'grounded'     => $grounded,
                        'citations'    => $citations,
                        'html_content' => $finalHtml,
                    ], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                    echo 'event: complete' . "\n";
                    echo 'data: ' . $json . "\n\n";
                    @ob_flush();
                    flush();
                } catch (\Throwable $e) {
                    Log::error('SSE complete JSON encoding failed', ['error' => $e->getMessage()]);
                }
            };

            // Delegate to the orchestrator to keep controller thin
            $this->orchestrator->handleStreamMessage(
                $session,
                $userContent,
                $userMessage,
                $assistantMessage,
                $emitActivity,
                $emitToken,
                $emitError,
                $emitComplete
            );

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}