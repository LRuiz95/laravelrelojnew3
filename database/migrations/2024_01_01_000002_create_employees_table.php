<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('user_id');
            $table->unsignedInteger('uid');
            $table->string('name');
            $table->unsignedTinyInteger('role')->default(0);
            $table->string('card_no')->nullable();
            $table->string('password')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['device_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
