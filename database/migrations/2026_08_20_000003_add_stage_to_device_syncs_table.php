<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_syncs', function (Blueprint $table): void {
            $table->string('stage', 30)->nullable()->after('operation');
        });
    }

    public function down(): void
    {
        Schema::table('device_syncs', function (Blueprint $table): void {
            $table->dropColumn('stage');
        });
    }
};
