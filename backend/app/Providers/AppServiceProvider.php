<?php

namespace App\Providers;

use App\Services\AI\OllamaLLMGateway;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Services\AI\OllamaEmbeddingProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // LLM Gateway — uses LLMProviderManager for fallback and configuration
        $this->app->bind(LLMGatewayInterface::class, function ($app) {
            return $app->make(\App\Services\AI\LLMProviderManager::class);
        });

        // Embedding Provider — uses EmbeddingProviderManager for dynamic selection
        $this->app->bind(EmbeddingProviderInterface::class, function ($app) {
            return $app->make(\App\Services\AI\EmbeddingProviderManager::class);
        });
    }

    public function boot(): void
    {
        // Point Laravel's password-reset notification at the Vue SPA reset page
        // instead of the non-existent Blade route (API-only app, Req 1.6).
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontend = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            return "{$frontend}/reset-password?token={$token}&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });

        // Use custom PersonalAccessToken model supporting standard tokens and Supabase JWTs
        Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);

        // Sanctum must not authenticate soft-deleted users (Req 2.4).
        Sanctum::authenticateAccessTokensUsing(
            static function ($token, bool $isValid) {
                return $isValid && ! $token->tokenable->trashed();
            }
        );

        // Apple Socialite Provider Event Listener
        if (class_exists(\SocialiteProviders\Manager\SocialiteWasCalled::class) && class_exists(\SocialiteProviders\Apple\AppleExtendSocialite::class)) {
            Event::listen(
                \SocialiteProviders\Manager\SocialiteWasCalled::class,
                \SocialiteProviders\Apple\AppleExtendSocialite::class . '@handle'
            );
        }
    }
}