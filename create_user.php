<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call(function () {
    $user = \App\Models\User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('secret123'),
        'role' => 'admin',
    ]);
    echo "Usuario creado: ID=".$user->id.", Name=".$user->name.", Email=".$user->email.", Role=".$user->role;
});