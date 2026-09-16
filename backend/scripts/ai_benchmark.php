<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\ChatSession;
use App\Models\User;
use App\Services\Chat\ChatOrchestrator;
use Illuminate\Contracts\Console\Kernel;

$orchestrator = app(ChatOrchestrator::class);

$testQueries = [
    // Book questions
    ['q' => 'What is e-Governance?', 'type' => 'Book question'],
    ['q' => 'What is the main takeaway of Chapter 2?', 'type' => 'Book question'],
    // Platform questions
    ['q' => 'What is Smart Adama platform?', 'type' => 'Platform question'],
    ['q' => 'How does the study reader work?', 'type' => 'Platform question'],
    // Developer questions
    ['q' => 'Who is Ashenafi Deresa Feyisa?', 'type' => 'Developer question'],
    ['q' => 'What did Abinet Tesfaye do?', 'type' => 'Developer question'],
    // Out-of-Scope
    ['q' => 'Who won the World Cup in 2022?', 'type' => 'Out-of-Scope'],
    ['q' => 'Write a python script to sort an array.', 'type' => 'Out-of-Scope'],
    // Short queries
    ['q' => 'Innovation', 'type' => 'Short query'],
    ['q' => 'Kidus', 'type' => 'Short query'],
    // Multilingual
    ['q' => 'Smart Adama jechuun maal jechuudha?', 'type' => 'Multilingual'],
];

$user = User::first();
if (! $user) {
    $user = User::factory()->create();
}
$session = ChatSession::create(['title' => 'Benchmark Session', 'user_id' => $user->id]);
$results = [];

echo "Starting benchmark...\n";

foreach ($testQueries as $index => $test) {
    echo "Testing: [{$test['type']}] '{$test['q']}'...\n";
    $startTime = microtime(true);

    try {
        $result = $orchestrator->handleMessage($session, $test['q']);
        $latency = microtime(true) - $startTime;
        $status = 'PASS';
        $answerSnippet = substr(strip_tags($result['message']->content), 0, 100);
    } catch (Exception $e) {
        $latency = microtime(true) - $startTime;
        $status = 'FAIL';
        $answerSnippet = 'ERROR: '.$e->getMessage();
    }

    $results[] = [
        'Query' => $test['q'],
        'Type' => $test['type'],
        'Latency' => round($latency, 2),
        'Status' => $status,
        'Snippet' => $answerSnippet,
    ];
}

echo "\n--- RESULTS ---\n";
foreach ($results as $res) {
    echo str_pad($res['Type'], 20).' | '.str_pad($res['Latency'].'s', 6)." | {$res['Status']} | {$res['Snippet']}\n";
}

$session->delete();
