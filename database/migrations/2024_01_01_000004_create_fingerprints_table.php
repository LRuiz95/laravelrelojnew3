<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('finger')->comment('Dedo ZKTeco 0-9');
            $table->binary('template');
            $table->string('template_hash', 64);
            $table->timestamps();

            $table->unique(['device_id', 'employee_id', 'finger']);
            $table->index(['device_id', 'template_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprints');
    }
};
