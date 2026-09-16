<?php

use App\Models\PersonalAccessToken;
use Tests\TestCase;

uses(TestCase::class);

it('returns null for malformed or non-JWT tokens', function () {
    expect(PersonalAccessToken::findToken(''))->toBeNull();
    expect(PersonalAccessToken::findToken('not-a-jwt'))->toBeNull();
    expect(PersonalAccessToken::findToken('foo.bar'))->toBeNull();
});

it('returns null for an expired Supabase JWT', function () {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'sub' => 'expired-user-id',
        'email' => 'expired@example.com',
        'aud' => 'authenticated',
        'exp' => time() - 3600,
    ]));
    $jwt = $header.'.'.$payload.'.dummy_signature';

    $token = PersonalAccessToken::findToken($jwt);

    expect($token)->toBeNull();
});
