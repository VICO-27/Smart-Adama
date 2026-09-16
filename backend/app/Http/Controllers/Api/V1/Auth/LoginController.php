<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Utils\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * POST /api/v1/auth/login
 * Requirement 1.3, 1.4 — throttle 5 attempts per email/IP per 10 min.
 */
class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $identifier = $request->input('identifier') ?? $request->input('email');
        $credential = $request->input('credential') ?? $request->input('password');

        // Try normalizing as phone number, or keep as is for email
        $normalizedPhone = PhoneNormalizer::normalize($identifier);

        $throttleKey = 'login:'.strtolower($identifier).'|'.$request->ip();

        // Req 1.4 — throttle after 5 failed attempts
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'error' => [
                    'code' => 'TOO_MANY_REQUESTS',
                    'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ],
            ], 429)->withHeaders(['Retry-After' => $seconds]);
        }

        $user = User::where('email', $identifier)
            ->when($normalizedPhone, function ($query) use ($normalizedPhone) {
                $query->orWhere('phone_number', $normalizedPhone);
            })
            ->first();

        if (! $user || ! Hash::check($credential, $user->password)) {
            RateLimiter::hit($throttleKey, 60 * 10); // 10 minute window

            $field = $request->has('email') && ! $request->has('identifier') ? 'email' : 'identifier';

            throw ValidationException::withMessages([
                $field => ['The provided credentials are incorrect.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }
}
