<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('devices')->whereNotNull('password')->orderBy('id')->chunkById(100, function ($devices): void {
            foreach ($devices as $device) {
                DB::table('devices')->where('id', $device->id)->update([
                    'password' => Crypt::encryptString((string) $device->password),
                ]);
            }
        });

        DB::table('employees')->whereNotNull('password')->orderBy('id')->chunkById(100, function ($employees): void {
            foreach ($employees as $employee) {
                DB::table('employees')->where('id', $employee->id)->update([
                    'password' => Crypt::encryptString((string) $employee->password),
                ]);
            }
        });

        DB::table('fingerprints')->orderBy('id')->chunkById(100, function ($fingerprints): void {
            foreach ($fingerprints as $fingerprint) {
                DB::table('fingerprints')->where('id', $fingerprint->id)->update([
                    'template' => Crypt::encryptString((string) $fingerprint->template),
                ]);
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('No es posible revertir cifrado sensible sin exponer los datos.');
    }
};
