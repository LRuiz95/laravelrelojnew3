<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_sync_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('device_sync_id')->constrained('device_syncs')->cascadeOnDelete();
            $table->foreignId('fingerprint_id')->nullable()->constrained('fingerprints')->nullOnDelete();
            $table->string('credential_type', 20);
            $table->unsignedTinyInteger('finger')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('message')->nullable();
            $table->timestamps();

            $table->unique(['device_sync_id', 'credential_type', 'finger'], 'device_sync_items_identity_unique');
            $table->index(['device_sync_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_sync_items');
    }
};
