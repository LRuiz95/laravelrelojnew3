<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip')->unique();
            $table->unsignedSmallInteger('port')->default(4370);
            $table->string('password')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('device_name')->nullable();
            $table->enum('status', ['online', 'offline', 'unknown'])->default('unknown');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
