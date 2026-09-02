<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        Device::firstOrCreate(
            ['ip' => env('ZKTECO_DEFAULT_IP', '192.168.100.68')],
            [
                'name' => 'Checador Principal',
                'port' => (int) env('ZKTECO_DEFAULT_PORT', 4370),
                'password' => env('ZKTECO_DEFAULT_PASSWORD', ''),
                'status' => 'unknown',
                'description' => 'Dispositivo ZKTeco configurado por defecto.',
            ]
        );
    }
}
