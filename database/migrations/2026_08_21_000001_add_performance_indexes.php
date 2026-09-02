<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->index(['device_id', 'recorded_at'], 'att_device_recorded_idx');
            $table->index(['state', 'recorded_at'], 'att_state_recorded_idx');
            $table->index(['employee_id', 'recorded_at', 'id'], 'att_employee_recorded_id_idx');
        });

        Schema::table('devices', function (Blueprint $table): void {
            $table->index(['status', 'updated_at'], 'devices_status_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropIndex('att_device_recorded_idx');
            $table->dropIndex('att_state_recorded_idx');
            $table->dropIndex('att_employee_recorded_id_idx');
        });

        Schema::table('devices', function (Blueprint $table): void {
            $table->dropIndex('devices_status_updated_idx');
        });
    }
};
