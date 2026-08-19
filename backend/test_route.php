<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/v1/auth/google/redirect', 'GET');
$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Headers:\n" . $response->headers . "\n";
echo "Content:\n" . $response->getContent() . "\n";
