<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call(function () {
    $d = \App\Models\Device::create(['name' => 'Test', 'ip' => '192.168.1.1', 'port' => 4370]);
    echo 'Status: ' . $d->status . PHP_EOL;
});