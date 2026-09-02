<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_syncs', function (Blueprint $table) {
            $table->string('operation', 20)->nullable()->after('status');
            $table->unsignedBigInteger('employee_id')->nullable()->after('operation');
            $table->unsignedInteger('total')->default(0)->after('processed');
        });
    }

    public function down(): void
    {
        Schema::table('device_syncs', function (Blueprint $table) {
            $table->dropColumn(['operation', 'employee_id', 'total']);
        });
    }
};
