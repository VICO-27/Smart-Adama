<?php

use App\Http\Controllers\Api\GlobalChatController;
use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Services\AI\ReasoningFilter;

it('preserves clean model output without reasoning tags', function () {
    $fakeLlm = Mockery::mock(LLMGatewayInterface::class);
    $controller = new GlobalChatController($fakeLlm);

    $raw = 'Hello! I am the Smart Adama Help Center assistant. How can I help you today?';
    expect($controller->stripInternalReasoning($raw))->toBe($raw);
});

it('strips closed think tags and internal reasoning', function () {
    $fakeLlm = Mockery::mock(LLMGatewayInterface::class);
    $controller = new GlobalChatController($fakeLlm);

    $raw = "<think>The user is asking how to reset their PIN.\nI should explain the Phone + PIN flow.\n</think>To reset your PIN, click 'Forgot PIN?' in the sign-in modal.";
    $expected = "To reset your PIN, click 'Forgot PIN?' in the sign-in modal.";

    expect($controller->stripInternalReasoning($raw))->toBe($expected);
});

it('strips multiline think tags case-insensitively', function () {
    $fakeLlm = Mockery::mock(LLMGatewayInterface::class);
    $controller = new GlobalChatController($fakeLlm);

    $raw = "<THINK>\nFirst step: check route.\nSecond step: formulate guidance.\n</THINK>\n\nWelcome to Smart Adama!";
    expect($controller->stripInternalReasoning($raw))->toBe('Welcome to Smart Adama!');
});

it('strips unclosed think tags when response is truncated', function () {
    $fakeLlm = Mockery::mock(LLMGatewayInterface::class);
    $controller = new GlobalChatController($fakeLlm);

    $raw = '<think>I am analyzing the query and checking if the user is asking about book content or';
    expect($controller->stripInternalReasoning($raw))->toBe('');
});

it('strips Thinking process blocks with answer marker', function () {
    $fakeLlm = Mockery::mock(LLMGatewayInterface::class);
    $controller = new GlobalChatController($fakeLlm);

    $raw = "Thinking process:\n1. User forgot password\n2. Provide real flow\n\nAnswer:\nEnter your phone number and verify the 6-digit code sent to your email.";
    $expected = 'Enter your phone number and verify the 6-digit code sent to your email.';

    expect($controller->stripInternalReasoning($raw))->toBe($expected);
});

it('strips leading Thinking process blocks without explicit answer marker', function () {
    $fakeLlm = Mockery::mock(LLMGatewayInterface::class);
    $controller = new GlobalChatController($fakeLlm);

    $raw = "**Thinking Process:**\nThe user wants to know who built Smart Adama.\n\nSmart Adama was built by Group 2 interns.";
    $expected = 'Smart Adama was built by Group 2 interns.';

    expect($controller->stripInternalReasoning($raw))->toBe($expected);
});

it('strips Analysis blocks followed by response marker', function () {
    $fakeLlm = Mockery::mock(LLMGatewayInterface::class);
    $controller = new GlobalChatController($fakeLlm);

    $raw = "Analysis:\nThe user asked about dark mode settings.\n\nResponse:\nYou can switch themes using the theme controls in the assistant.";
    $expected = 'You can switch themes using the theme controls in the assistant.';

    expect($controller->stripInternalReasoning($raw))->toBe($expected);
});

it('strips leading Analysis blocks without response marker', function () {
    $fakeLlm = Mockery::mock(LLMGatewayInterface::class);
    $controller = new GlobalChatController($fakeLlm);

    $raw = "Analysis:\nUser is on the dashboard.\n\nWelcome to your learning dashboard!";
    $expected = 'Welcome to your learning dashboard!';

    expect($controller->stripInternalReasoning($raw))->toBe($expected);
});

it('strips Chain of thought blocks', function () {
    $fakeLlm = Mockery::mock(LLMGatewayInterface::class);
    $controller = new GlobalChatController($fakeLlm);

    $raw = "Chain of thought:\n- Step 1: Identify request\n- Step 2: Formulate response\n\nFinal Answer:\nAnswer text.";
    $expected = 'Answer text.';

    expect($controller->stripInternalReasoning($raw))->toBe($expected);
});

it('handles empty or whitespace strings cleanly', function () {
    $fakeLlm = Mockery::mock(LLMGatewayInterface::class);
    $controller = new GlobalChatController($fakeLlm);

    expect($controller->stripInternalReasoning(''))->toBe('');
    expect($controller->stripInternalReasoning("   \n\t  "))->toBe('');
});

it('strips reasoning blocks via static ReasoningFilter::strip', function () {
    expect(ReasoningFilter::strip('<think>internal thoughts</think>User visible text'))
        ->toBe('User visible text');
    expect(ReasoningFilter::strip("Thinking Process:\nSome thoughts\n\nFinal Answer:\nVisible"))
        ->toBe('Visible');
    expect(ReasoningFilter::strip(null))
        ->toBe('');
});
