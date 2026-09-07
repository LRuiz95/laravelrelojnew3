<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "Total attendances: " . Illuminate\Support\Facades\DB::table('attendances')->count() . PHP_EOL;
$rows = Illuminate\Support\Facades\DB::table('attendances')
    ->select('device_id', 'employee_id', 'recorded_at')
    ->groupBy(['device_id', 'employee_id', 'recorded_at'])
    ->havingRaw('COUNT(*) > 1')
    ->get();
echo "Duplicate combinations (device_id, employee_id, recorded_at): " . $rows->count() . PHP_EOL;
foreach ($rows as $row) {
    echo "  device_id={$row->device_id}, employee_id={$row->employee_id}, recorded_at={$row->recorded_at}" . PHP_EOL;
}
echo PHP_EOL . "Employees count: " . Illuminate\Support\Facades\DB::table('employees')->count() . PHP_EOL;
echo "Devices count: " . Illuminate\Support\Facades\DB::table('devices')->count() . PHP_EOL;