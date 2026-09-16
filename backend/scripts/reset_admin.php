<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'admin@smartadama.com')->first();

if ($user) {
    $user->password = Hash::make('password');
    $user->save();
    echo "Admin password reset to: password\n";
    echo "Email: admin@smartadama.com\n";
    echo 'Role: '.$user->role."\n";
} else {
    echo "Admin user not found\n";
}
