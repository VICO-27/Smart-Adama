<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Find the token instance for the given token string.
     * Seamlessly supports standard Sanctum tokens AND Supabase Auth JWT tokens.
     *
     * @param  string  $token
     * @return static|null
     */
    public static function findToken($token)
    {
        // Check if token is a Supabase JWT (3 base64 segments separated by dots)
        if (substr_count($token, '.') === 2) {
            $parts = explode('.', $token);
            $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'));
            $payload = json_decode($payloadJson, true);

            if (is_array($payload) && (isset($payload['sub']) || isset($payload['email']))) {
                // Check token expiration if exp claim is present
                if (isset($payload['exp']) && $payload['exp'] < time()) {
                    return null;
                }

                // Normalise empty strings to null — anonymous Supabase JWTs carry
                // email="" and phone="" which would violate the UNIQUE constraints
                // on users.email / users.phone_number for a second anonymous sign-in.
                $email = !empty($payload['email']) ? $payload['email'] : null;
                $phone = !empty($payload['phone']) ? $payload['phone'] : null;
                $sub = $payload['sub'] ?? null;
                $isAnonymous = ! empty($payload['is_anonymous'])
                    || (($payload['app_metadata']['provider'] ?? null) === 'anonymous');

                $user = null;
                if ($email) {
                    $user = User::where('email', $email)->first();
                }
                if (! $user && $phone) {
                    $user = User::where('phone_number', $phone)->first();
                }
                if (! $user && $sub) {
                    $user = User::where('provider_id', $sub)->first();
                }

                // If user doesn't exist yet, auto-provision from Supabase metadata
                if (! $user) {
                    $meta = $payload['user_metadata'] ?? [];
                    $name = $meta['name']
                        ?? $meta['full_name']
                        ?? ($email ? explode('@', $email)[0] : ($isAnonymous ? 'Guest Learner' : 'Citizen'));

                    $user = User::create([
                        'id' => (string) Str::uuid(),
                        'name' => $name,
                        'email' => $email,
                        'phone_number' => $phone,
                        'email_verified_at' => $isAnonymous ? null : now(),
                        'password' => bcrypt(Str::random(32)),
                        'role' => 'learner',
                        'status' => 'active',
                        'provider' => $isAnonymous ? 'anonymous' : 'supabase',
                        'provider_id' => $sub,
                    ]);
                }

                // Ensure anonymous users NEVER inherit or retain admin role
                if ($isAnonymous && $user->role === 'admin') {
                    $user->role = 'learner';
                }

                $user->is_anonymous = $isAnonymous;

                // Return transient token instance that satisfies Sanctum Guard
                $tokenInstance = new static([
                    'name' => $isAnonymous ? 'supabase_anon_jwt' : 'supabase_jwt',
                    'token' => hash('sha256', $token),
                    'abilities' => ['*'],
                ]);

                $tokenInstance->exists = true;
                $tokenInstance->created_at = isset($payload['iat'])
                    ? Carbon::createFromTimestamp($payload['iat'])
                    : now();
                $tokenInstance->expires_at = isset($payload['exp'])
                    ? Carbon::createFromTimestamp($payload['exp'])
                    : now()->addMonth();

                $tokenInstance->setRelation('tokenable', $user);

                return $tokenInstance;
            }
        }

        // Standard Sanctum personal access token lookup
        return parent::findToken($token);
    }
}
