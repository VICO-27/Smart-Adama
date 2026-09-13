<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\GlobalChatController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\PinRecoveryController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Books\AdminBookController;
use App\Http\Controllers\Api\V1\Books\AdminChapterController;
use App\Http\Controllers\Api\V1\Books\AdminSectionController;
use App\Http\Controllers\Api\V1\Books\AdminBookIngestionController;
use App\Http\Controllers\Api\V1\Books\BookController;
use App\Http\Controllers\Api\V1\Books\ChapterController;
use App\Http\Controllers\Api\V1\Chat\ChatMessageController;
use App\Http\Controllers\Api\V1\Chat\ChatMessageFeedbackController;
use App\Http\Controllers\Api\V1\Chat\ChatSessionController;
use App\Http\Controllers\Api\V1\Dashboard\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Gamification\BadgeController;
use App\Http\Controllers\Api\V1\Gamification\StreakController;
use App\Http\Controllers\Api\V1\Health\HealthController;
use App\Http\Controllers\Api\V1\Progress\ProgressController;
use App\Http\Controllers\Api\V1\Quizzes\AdminQuizController;
use App\Http\Controllers\Api\V1\Quizzes\AdminQuizQuestionController;
use App\Http\Controllers\Api\V1\Quizzes\QuizAttemptController;
use App\Http\Controllers\Api\V1\Quizzes\QuizController;
use App\Http\Controllers\Api\V1\Users\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Gamification\GameController;

Route::middleware('auth:sanctum')->get('/v1/ai-search', [SearchController::class, 'search']);



Route::get('/health', HealthController::class);

Route::prefix('auth')->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class);
    Route::post('/password/forgot', [PasswordResetController::class, 'forgot']);
    Route::post('/password/reset', [PasswordResetController::class, 'reset']);

    // --- NEW: PIN Recovery Routes ---
    Route::post('/pin/forgot', [PinRecoveryController::class, 'verifyPhone']);
    Route::post('/pin/verify-code', [PinRecoveryController::class, 'verifyCode']);
    Route::post('/pin/reset', [PinRecoveryController::class, 'resetPin']);

    // --- NEW: Socialite Routes ---
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback']);
});

Route::get('/latest-book-id', function () {
    return \App\Models\Book::orderBy('created_at', 'desc')->first()->id;
});

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', LogoutController::class);
    Route::get('/auth/me', MeController::class);

    // --- Gamification Game Hub Routes ---
    Route::prefix('game')->group(function () {
        Route::get('/leaderboard', [GameController::class, 'leaderboard']);
        Route::get('/daily-challenge', [GameController::class, 'dailyChallenge']);
    });

    Route::prefix('users/me')->group(function () {
        Route::get('/', [UserController::class, 'show']);
        Route::patch('/', [UserController::class, 'update']);

        // --- NEW: Password Update Route ---
        Route::put('/password', [UserController::class, 'updatePassword']);

        Route::post('/avatar', [UserController::class, 'uploadAvatar']);
        Route::delete('/', [UserController::class, 'destroy']);
        Route::get('/quiz-attempts', [QuizAttemptController::class, 'index']);
    });

    Route::get('/books', [BookController::class, 'index']);
    Route::get('/chapters/{chapter}', [ChapterController::class, 'show']);
    Route::post('/chapters/{chapter}/read', [ChapterController::class, 'markRead']);
    Route::get('/chapters/{chapter}/quiz', [QuizController::class, 'showByChapter']);

    Route::prefix('chat/sessions')->group(function () {
        Route::get('/', [ChatSessionController::class, 'index']);
        Route::post('/', [ChatSessionController::class, 'store']);
        Route::delete('/', [ChatSessionController::class, 'destroyAll']);
        Route::get('/{session}', [ChatSessionController::class, 'show']);
        Route::patch('/{session}', [ChatSessionController::class, 'update']);
        Route::delete('/{session}', [ChatSessionController::class, 'destroy']);
        Route::post('/{session}/messages', [ChatMessageController::class, 'store'])
            ->middleware('throttle.chat');
    });

    Route::post('/quizzes/{quiz}/attempts', [QuizAttemptController::class, 'store']);
    Route::post('/quizzes/{quiz}/attempts/{attempt}/submit', [QuizAttemptController::class, 'submit']);

    Route::get('/users/me/progress', [ProgressController::class, 'show']);
    Route::get('/users/me/badges', [BadgeController::class, 'index']);
    Route::get('/users/me/streak', [StreakController::class, 'show']);
    Route::get('/dashboard', [DashboardController::class, 'show']);

    // --- Chat Message Feedback (👍/👎) ---
    Route::prefix('messages/{message}/feedback')->group(function () {
        Route::post('/', [ChatMessageFeedbackController::class, 'store']);
        Route::delete('/', [ChatMessageFeedbackController::class, 'destroy']);
        Route::get('/', [ChatMessageFeedbackController::class, 'index']);
    });
});

// --- Global Assistant Route (Accessible to Guests & Authenticated Users, Rate-Limited) ---
Route::post('/global-chat', [GlobalChatController::class, 'handle'])
    ->middleware('throttle.chat');

// ── Admin Routes ─────────────────────────────────────────────────────────────
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    // Analytics
    Route::get('/analytics', AdminAnalyticsController::class);

    // Books
    Route::post('/books', [AdminBookController::class, 'store']);
    Route::get('/books/{book}', [AdminBookController::class, 'show']);
    Route::delete('/books/{book}', [AdminBookController::class, 'destroy']);

    // Chapters - GET routes must come before POST/PUT/PATCH to avoid matching {chapter} IDs
    Route::get('/chapters/{chapter}/sections', [AdminSectionController::class, 'index']);
    Route::get('/chapters/{chapter}/status', [AdminBookIngestionController::class, 'getChapterStatus']);

    // Chapters
    Route::post('/books/{book}/chapters', [AdminChapterController::class, 'store']);
    Route::patch('/chapters/{chapter}', [AdminChapterController::class, 'update']);
    Route::post('/chapters/{chapter}/publish', [AdminChapterController::class, 'publish']);

    // Sections
    Route::post('/chapters/{chapter}/sections', [AdminSectionController::class, 'store']);
    Route::patch('/sections/{section}', [AdminSectionController::class, 'update']);
    Route::delete('/sections/{section}', [AdminSectionController::class, 'destroy']);
    Route::patch('/sections/{section}/reorder', [AdminSectionController::class, 'reorder']);

    // Quizzes
    Route::post('/chapters/{chapter}/generate-quiz', [AdminQuizController::class, 'generate']);
    Route::post('/chapters/{chapter}/quizzes', [AdminQuizController::class, 'store']);
    Route::post('/quizzes/{quiz}/publish', [AdminQuizController::class, 'publish']);

    // Quiz Questions
    Route::post('/quizzes/{quiz}/questions', [AdminQuizQuestionController::class, 'store']);
    Route::patch('/quizzes/{quiz}/questions/{question}', [AdminQuizQuestionController::class, 'update']);
    Route::delete('/quizzes/{quiz}/questions/{question}', [AdminQuizQuestionController::class, 'destroy']);

    // Book Ingestion (Manual)
    Route::get('/book-ingestion', [AdminBookIngestionController::class, 'index']);
    Route::put('/chapters/{chapter}/content', [AdminBookIngestionController::class, 'updateChapter']);
    Route::post('/chapters/{chapter}/validate', [AdminBookIngestionController::class, 'validateChapter']);
    Route::post('/chapters/{chapter}/preview', [AdminBookIngestionController::class, 'previewChapter']);
    Route::post('/chapters/{chapter}/preview-structured', [AdminBookIngestionController::class, 'previewStructured']);
    Route::post('/chapters/{chapter}/ingest', [AdminBookIngestionController::class, 'ingestChapter']);
    Route::post('/chapters/{chapter}/ingest-structured', [AdminBookIngestionController::class, 'ingestStructured']);
    Route::post('/chapters/{chapter}/retry', [AdminBookIngestionController::class, 'retryFailed']);
    Route::get('/chapters/{chapter}/status', [AdminBookIngestionController::class, 'getChapterStatus']);
    Route::post('/books/{book}/verify', [AdminBookIngestionController::class, 'verifyBook']);

    // System
    Route::get('/system/health', \App\Http\Controllers\Api\V1\Dashboard\AdminSystemController::class);
    Route::get('/rag/debug-search', [\App\Http\Controllers\Api\V1\RAG\DebugRetrievalController::class, 'search']);

    // Manual Content Authoring
    Route::post('/manual/books', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'createBook']);
    Route::get('/manual/books/{book}/tree', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'getTree']);
    Route::post('/manual/books/{book}/chapters', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'createChapter']);
    Route::put('/manual/chapters/{chapter}', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'updateChapter']);
    Route::delete('/manual/chapters/{chapter}', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'deleteChapter']);
    Route::post('/manual/chapters/{chapter}/sections', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'createSection']);
    Route::put('/manual/sections/{section}', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'updateSection']);
    Route::delete('/manual/sections/{section}', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'deleteSection']);
    Route::post('/manual/books/{book}/pages', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'createPage']);
    Route::put('/manual/pages/{page}', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'updatePage']);
    Route::delete('/manual/pages/{page}', [\App\Http\Controllers\Api\V1\Dashboard\ManualAuthoringController::class, 'deletePage']);

    // Ingestion Jobs (SSE & Control)
    Route::post('/ingestion-jobs/start', [\App\Http\Controllers\Api\V1\Dashboard\IngestionJobController::class, 'start']);
    Route::get('/ingestion-jobs/active', [\App\Http\Controllers\Api\V1\Dashboard\IngestionJobController::class, 'getActiveJobs']);
    Route::get('/ingestion-jobs/{job}/logs', [\App\Http\Controllers\Api\V1\Dashboard\IngestionJobController::class, 'getLogs']);
    Route::get('/ingestion-jobs/stream', [\App\Http\Controllers\Api\V1\Dashboard\IngestionJobController::class, 'streamProgress']);
    Route::post('/ingestion-jobs/{job}/pause', [\App\Http\Controllers\Api\V1\Dashboard\IngestionJobController::class, 'pause']);
    Route::post('/ingestion-jobs/{job}/resume', [\App\Http\Controllers\Api\V1\Dashboard\IngestionJobController::class, 'resume']);
    Route::post('/ingestion-jobs/{job}/cancel', [\App\Http\Controllers\Api\V1\Dashboard\IngestionJobController::class, 'cancel']);
    Route::post('/ingestion-jobs/{job}/retry', [\App\Http\Controllers\Api\V1\Dashboard\IngestionJobController::class, 'retry']);

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Api\V1\Admin\AdminNotificationController::class, 'index']);
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\V1\Admin\AdminNotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\Api\V1\Admin\AdminNotificationController::class, 'markAsRead']);
    Route::post('/notifications/seed', [\App\Http\Controllers\Api\V1\Admin\AdminNotificationController::class, 'seedMock']);

    // Users
    Route::get('/users', [\App\Http\Controllers\Api\V1\Users\AdminUserController::class, 'index']);
    Route::post('/users/invite', [\App\Http\Controllers\Api\V1\Users\AdminUserController::class, 'invite']);
    Route::get('/users/{user}', [\App\Http\Controllers\Api\V1\Users\AdminUserController::class, 'show']);

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Api\V1\Dashboard\AdminSettingsController::class, 'index']);
    Route::put('/settings', [\App\Http\Controllers\Api\V1\Dashboard\AdminSettingsController::class, 'update']);

    // RAG Document Management
    Route::get('/rag/documents', [\App\Http\Controllers\Api\V1\RAG\DocumentManagementController::class, 'index']);
    Route::get('/rag/documents/{id}/progress', [\App\Http\Controllers\Api\V1\RAG\DocumentManagementController::class, 'progress']);
});