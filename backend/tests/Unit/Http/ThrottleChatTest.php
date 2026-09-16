<?php

namespace Tests\Unit\Http;

use App\Http\Middleware\ThrottleChat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    RateLimiter::clear('chat:user:1');
    RateLimiter::clear('chat:ip:127.0.0.1');
    config(['ai.chat_rate_limit.max_attempts' => 2, 'ai.chat_rate_limit.decay_minutes' => 5]);
});

it('keys rate limiting by user id when authenticated', function () {
    $user = new User(['name' => 'Alice', 'email' => 'alice@test.com']);
    $user->id = 1;

    $middleware = new ThrottleChat;

    $request = Request::create('/api/global-chat', 'POST');
    $request->setUserResolver(fn () => $user);

    // Attempt 1
    $response = $middleware->handle($request, fn () => new Response('ok', 200));
    expect($response->getStatusCode())->toBe(200);

    // Attempt 2
    $response = $middleware->handle($request, fn () => new Response('ok', 200));
    expect($response->getStatusCode())->toBe(200);

    // Attempt 3: Exceeded
    $response = $middleware->handle($request, fn () => new Response('ok', 200));
    expect($response->getStatusCode())->toBe(429);
    $data = json_decode($response->getContent(), true);
    expect($data['error']['code'])->toBe('TOO_MANY_REQUESTS');
    expect($response->headers->has('Retry-After'))->toBeTrue();
});

it('keys rate limiting by client IP when unauthenticated (guest)', function () {
    $middleware = new ThrottleChat;

    $request = Request::create('/api/global-chat', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
    $request->setUserResolver(fn () => null);

    // Attempt 1
    $response = $middleware->handle($request, fn () => new Response('ok', 200));
    expect($response->getStatusCode())->toBe(200);

    // Attempt 2
    $response = $middleware->handle($request, fn () => new Response('ok', 200));
    expect($response->getStatusCode())->toBe(200);

    // Attempt 3: Exceeded
    $response = $middleware->handle($request, fn () => new Response('ok', 200));
    expect($response->getStatusCode())->toBe(429);
    $data = json_decode($response->getContent(), true);
    expect($data['error']['code'])->toBe('TOO_MANY_REQUESTS');
});
