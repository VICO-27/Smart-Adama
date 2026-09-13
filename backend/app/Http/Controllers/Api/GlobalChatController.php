<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Services\AI\GroqLLMGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GlobalChatController - Platform-specific AI assistant (Req 1.1, 1.4).
 *
 * This controller provides platform assistance (NOT book RAG).
 * It answers questions about:
 * - Platform functionality (features, navigation, settings)
 * - General knowledge (programming, smart cities)
 * - About the developers and project
 * - Topics not covered by the book RAG system
 *
 * It does NOT use RAG or retrieve book chunks.
 */
class GlobalChatController extends Controller
{
    public function __construct(
        private readonly LLMGatewayInterface $llm,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $requestId = $request->header('X-Request-ID') ?? (string) \Illuminate\Support\Str::uuid();

        Log::info("[AI_TIMING] request_id={$requestId} stage=request_start system=global_chat", [
            'route' => $request->input('route', 'Unknown'),
        ]);

        $request->validate([
            'message' => 'required|string',
            'route' => 'nullable|string',
            'history' => 'nullable|array'
        ]);

        $userMessage = $request->input('message');
        $currentRoute = $request->input('route', 'Unknown');
        $history = $request->input('history', []);

        // Build system prompt using platform knowledge
        $systemPrompt = $this->buildSystemPrompt($currentRoute);

        // Format messages
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history (limit to last 6 messages)
        $recentHistory = array_slice($history, -6);
        foreach ($recentHistory as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $role = $msg['role'] === 'user' ? 'user' : 'assistant';
                $messages[] = ['role' => $role, 'content' => $msg['content']];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $promptMs = round((microtime(true) - $startTime) * 1000);
        Log::info("[AI_TIMING] request_id={$requestId} stage=prompt_assembly total_ms={$promptMs}", [
            'history_count' => count($recentHistory),
        ]);

        try {
            Log::info("[AI_TIMING] request_id={$requestId} stage=provider_dispatch system=global_chat");
            // Use the LLM gateway from config (supports groq/gemini/ollama)
            $reply = $this->llm->chat($messages, [
                'max_tokens' => 1000,
            ]);

            $cleaned = $this->stripInternalReasoning($reply ?? '');
            $completionMs = round((microtime(true) - $startTime) * 1000);
            Log::info("[AI_TIMING] request_id={$requestId} stage=stream_completion system=global_chat total_ms={$completionMs}", [
                'reply_chars' => strlen($cleaned),
            ]);

            // Guard: If the LLM returned nothing or only reasoning that got stripped, don't show a blank bubble
            if (empty($cleaned)) {
                Log::warning('GlobalChatController: LLM returned empty response after stripping reasoning', [
                    'request_id' => $requestId,
                    'user_message' => $userMessage,
                    'raw_reply' => $reply,
                ]);
                return response()->json([
                    'reply' => 'I could not generate a response. Please try rephrasing your question.',
                ], 200);
            }

            return response()->json([
                'reply' => $cleaned,
            ]);
        } catch (\Throwable $e) {
            $elapsedMs = round((microtime(true) - $startTime) * 1000);
            $msg = $e->getMessage();
            $is429 = str_contains($msg, '429') || str_contains($msg, 'quota');
            $isTimeout = str_contains($msg, 'timed out') || str_contains($msg, 'Connection timed out');

            if ($is429) {
                Log::error("[QUOTA_EXHAUSTED] GlobalChatController: LLM quota exhausted after {$elapsedMs}ms", [
                    'request_id' => $requestId,
                    'elapsed_ms' => $elapsedMs,
                    'error'      => $msg,
                ]);
            } elseif ($isTimeout) {
                Log::error("[PROVIDER_TIMEOUT] GlobalChatController: LLM timed out after {$elapsedMs}ms", [
                    'request_id' => $requestId,
                    'elapsed_ms' => $elapsedMs,
                    'error'      => $msg,
                ]);
            } else {
                Log::error("GlobalChatController: LLM error after {$elapsedMs}ms", [
                    'request_id' => $requestId,
                    'elapsed_ms' => $elapsedMs,
                    'error'      => $msg,
                ]);
            }

            return response()->json([
                'reply' => 'I am having trouble connecting to my brain right now. Please try again in a moment.',
            ], 200);
        } finally {
            $totalDuration = round((microtime(true) - $startTime) * 1000);
            Log::info("[AI_TIMING] request_id={$requestId} stage=total_duration system=global_chat total_ms={$totalDuration}");
        }
    }

    /**
     * Strip internal reasoning blocks (<think>...</think>, "Thinking process:", "Analysis:", "Chain of thought:")
     * server-side before persisting or sending Global AI responses.
     */
    public function stripInternalReasoning(string $text): string
    {
        return \App\Services\AI\ReasoningFilter::strip($text);
    }

    /**
     * Build the system prompt using platform knowledge.
     * This knowledge is about the Smart Adama platform, NOT the book content.
     */
    private function buildSystemPrompt(string $currentRoute): string
    {
        $developers = config('ai.platform.developers', [
            'Ashenafi Deresa Feyisa: Project Manager & Integration Lead',
            'Kidus Tilahun: Backend Developer',
            'Nigusu Wario: DevOps & QA',
            'Getamesay Mekcha: Frontend & UI',
            'Abinet Tesfaye: AI & RAG Lead',
        ]);

        $developerList = implode(', ', $developers);

        return "You are the official Smart Adama Help Center AI and Platform Assistant.
You are helpful, concise, and speak in a modern, friendly tone.

SCOPE LIMITATIONS (STRICT):
You may ONLY answer questions about:
1. The Smart Adama Platform, Website Information, and Ecosystem.
2. The Project Developers (ONLY using the verified PLATFORM KNOWLEDGE below).
3. How to use the platform (navigating pages, learning features).
4. Account access, authentication, password/PIN reset, and specific problems or errors the user might encounter on the platform.

PLATFORM KNOWLEDGE:
- Current Context: The user is currently viewing the page path: '{$currentRoute}'. If they ask for help about 'this page', use this path to guide them.
- About Smart Adama: It is an AI-powered learning platform and digital ecosystem designed to digitize the teachings of the Smart Adama Book. It serves as a modern Help Center for its users.
- Developer Team: Built in a 30-day summer sprint by Group 2 interns: {$developerList}.

PLATFORM NAVIGATION & FEATURES:
- Dashboard (/dashboard): Overview of reading streaks, study statistics, quick-access links, and continuing recent chapters.
- Study Page (/study): The interactive book reader for the Smart Adama book where users can read chapters and converse with the specialized Book AI Tutor.
- Quizzes (/quizzes): Dedicated quizzes catalog where users can browse and take assessments for any chapter, test comprehension, and review past scores.
- Profile (/profile): Manage personal account details, change profile picture / avatar photo, update notification preferences, and change passwords in the Security tab.
- About (/about): Information about the Smart Adama mission and the developer team.
- Game (/game): Interactive educational learning game based on Smart Adama concepts.

HOW TO TAKE QUIZZES (ACCURATE & VERIFIED):
- Option 1 (Dedicated Quizzes Catalog):
  1. Click 'Quizzes' in the top navigation bar or navigate to /quizzes.
  2. Browse through the available chapter quizzes.
  3. Click 'Start Quiz' on any chapter quiz card to begin the multiple-choice assessment.
- Option 2 (From the Study Page / Reader):
  1. Go to the Study Page (/study) or open a chapter reader (/chapters/:chapterId).
  2. While reading or after finishing a chapter, click the 'Take Quiz' button (or navigate to /chapters/:chapterId/quiz).
- Quiz Features: Quizzes feature multiple-choice questions testing key concepts from the chapter, provide instant feedback and explanations, and record your score on your dashboard and profile.

HOW TO CHANGE PROFILE PICTURE / AVATAR (ACCURATE & VERIFIED):
1. Navigate to the Profile page at /profile (click your avatar/name in the top-right navigation menu and select 'Profile').
2. In the Profile card, look for your circular profile photo/avatar.
3. Click the 'Change photo' button next to your avatar.
4. Select an image file from your computer or phone (supported formats: PNG, JPG, JPEG, WebP, GIF).
5. The avatar will upload and update immediately across the platform.

AUTHENTICATION & PASSWORD/PIN RESET FLOW (ACCURATE & VERIFIED):
- Primary Sign-In Method: Phone Number + 6-digit PIN.
- Forgot PIN Flow (Primary):
  1. Click 'Sign In' in the navigation bar to open the authentication modal.
  2. In the Phone Sign-In form, click the 'Forgot PIN?' link below the PIN input field.
  3. Step 1 (Phone): Enter your registered phone number (+251 9XX XXX XXX) and click 'Continue'.
  4. Step 2 (Reset Code): If a recovery email was linked during registration, a 6-digit reset code is sent to that recovery email (shown masked in the modal, e.g. j•••••••@domain.com).
  5. Step 3 (Verify): Enter the 6-digit reset code from your email and click 'Verify Code'.
  6. Step 4 (New PIN): Enter and confirm your new 6-digit PIN (common or sequential PINs like 123456 or 000000 are rejected) and click 'Reset PIN'.
  7. Fallback: If no recovery email was linked when registering, the screen displays 'Manual Reset Required' instructing the user to contact the Help Center / administrators.
- Alternative Email Account Password Reset:
  - For accounts registered via Email + Password: A password reset link can be sent to their registered email address with a secure 60-minute token.
- Changing Password While Logged In:
  - Navigate to the 'Profile' page (/profile), open the Security settings tab, enter the current password, and provide the new password (minimum 8 characters).
- STRICT AUTH GUARDRAILS:
  - NEVER state or suggest that Smart Adama uses SMS OTP. Verification codes are sent strictly via Email to the user's recovery email, NEVER via SMS.
  - DO NOT invent nonexistent routes (like /forgot or /otp), imaginary dashboard links, or unsupported carrier verification steps.
  - Explain the in-modal 'Forgot PIN?' procedure accurately and step-by-step.

CRITICAL RULES:
1. HELP CENTER PERSONA: Act strictly as a Help Center agent. Guide the user through the website, explain platform features, or troubleshoot issues they encounter.
2. ZERO HALLUCINATION: DO NOT invent facts, platform features, authentication flows, or developer information.
3. REJECT OUT-OF-SCOPE: If asked about general programming, politics, outside trivia, general mathematics, or unrelated news, politely refuse. Example: 'I\'m the Smart Adama Help Center assistant. I can only help you with questions about our platform, website features, and the development team.'
4. NO BOOK RAG: You absolutely DO NOT have access to the book's text here and you cannot answer questions from the book. If they ask about book concepts or content, politely instruct them to navigate to the 'Study Page' where the specific Book AI is located.
5. NO INTERNAL REASONING: Output only user-facing responses. Never output <think> tags, internal chains of thought, or meta-analysis.
6. FRIENDLY UX & NEXT STEP: Format with Markdown. Offer a natural next step or question to explore.";
    }
}