<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Detalle por tabla de cada sincronización Firebird
     */
    public function up(): void
    {
        Schema::create('firebird_sync_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('firebird_sync_id');
            $table->string('table_name')->comment('Tabla Firebird: CICLOS, GRUPOS, ALUMNOS, etc.');
            $table->enum('action', ['insert', 'update', 'delete', 'skip', 'error']);
            $table->string('record_id')->nullable()->comment('PK del registro afectado');
            $table->enum('status', ['success', 'error', 'skipped'])->default('success');
            $table->text('message')->nullable();
            $table->json('data_before')->nullable();
            $table->json('data_after')->nullable();
            $table->timestamps();

            $table->index(['firebird_sync_id', 'table_name'], 'idx_fsi_sync_table');
            $table->index('action', 'idx_fsi_action');
            $table->index('status', 'idx_fsi_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firebird_sync_items');
    }
};
