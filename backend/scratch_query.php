<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$chunks = \DB::table('content_chunks')->where('chunk_text', 'ilike', '%mayor%')->get(['chunk_text'])->toArray();
echo json_encode($chunks, JSON_PRETTY_PRINT);
