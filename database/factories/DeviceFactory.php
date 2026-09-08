<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Device ' . Str::random(5),
            'ip' => '192.168.1.' . rand(1, 254),
            'port' => 502,
            'password' => '1234',
            'description' => 'Device description',
            'serial_number' => 'SERIAL-' . rand(1000, 9999),
        ];
    }
}