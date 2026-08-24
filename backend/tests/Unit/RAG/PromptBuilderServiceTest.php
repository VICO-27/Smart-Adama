<?php

use App\Services\RAG\PromptBuilderService;

beforeEach(function () {
    $this->builder = new PromptBuilderService();
});

// ── PromptBuilderService ─────────────────────────────────────────────────────

it('includes a system message as the first element', function () {
    $messages = $this->builder->buildMessages([], [], 'What is Smart Adama?', ['isGrounded' => false], false);

    expect($messages[0]['role'])->toBe('system');
});

it('appends the user query as the last message', function () {
    $query    = 'What does Smart Adama say about leadership?';
    $messages = $this->builder->buildMessages([], [], $query, ['isGrounded' => false], false);

    $last = end($messages);
    expect($last['role'])->toBe('user')
        ->and($last['content'])->toBe($query);
});

it('uses the no-context system prompt when grounded=false', function () {
    $messages = $this->builder->buildMessages([], [], 'Some query', ['isGrounded' => false], false);

    expect($messages[0]['content'])->toContain('No reliable context was found');
});

it('uses the full system prompt with context when grounded=true', function () {
    $chunks = [[
        'chunk_text'         => 'Smart Adama is a visionary framework.',
        'page_number'        => 42,
        'structural_context' => ['heading' => 'Introduction'],
    ]];

    $messages = $this->builder->buildMessages([], $chunks, 'Tell me about Smart Adama', ['isGrounded' => true], false);

    expect($messages[0]['content'])
        ->toContain('TEXT: ')
        ->toContain('Smart Adama is a visionary framework.')
        ->toContain('[Page 42]');
});

it('injects conversation history between system and current user message', function () {
    $history = [
        ['role' => 'user',      'content' => 'Hello'],
        ['role' => 'assistant', 'content' => 'Hi there!'],
    ];

    $messages = $this->builder->buildMessages($history, [], 'Follow-up question', ['isGrounded' => false], false);

    // system, user(Hello), assistant(Hi), user(Follow-up)
    expect(count($messages))->toBe(4)
        ->and($messages[1]['role'])->toBe('user')
        ->and($messages[2]['role'])->toBe('assistant');
});

it('limits history to the most recent 20 messages', function () {
    // Build 30 messages alternating user/assistant
    $history = [];
    for ($i = 0; $i < 30; $i++) {
        $history[] = ['role' => $i % 2 === 0 ? 'user' : 'assistant', 'content' => "msg {$i}"];
    }

    $messages = $this->builder->buildMessages($history, [], 'New query', ['isGrounded' => false], false);

    // system + up to 20 history + 1 user query = max 22
    expect(count($messages))->toBeLessThanOrEqual(22);
});

it('builds a context block from multiple chunks with page numbers and headings', function () {
    $chunks = [
        ['chunk_text' => 'First chunk text.', 'page_number' => 10, 'structural_context' => ['heading' => 'S1']],
        ['chunk_text' => 'Second chunk text.', 'page_number' => 12, 'structural_context' => ['heading' => 'S2']],
    ];

    $messages = $this->builder->buildMessages([], $chunks, 'query', ['isGrounded' => true], false);

    expect($messages[0]['content'])
        ->toContain('[Page 10] First chunk text.')
        ->toContain('[Page 12] Second chunk text.');
});
