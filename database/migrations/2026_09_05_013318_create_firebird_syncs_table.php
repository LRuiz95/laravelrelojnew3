<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracking ETL Firebird → MySQL (sincronizar.php de ProyectoBase)
     */
    public function up(): void
    {
        Schema::create('firebird_syncs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('operation')->comment('sync_ciclo, sync_catalogos, sync_all');
            $table->string('ciclo')->nullable()->comment('Ciclo escolar: 2025-2025-3');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])
                ->default('pending');
            $table->string('stage')->nullable()->comment('Fase actual: preparando, ciclos_directos, alumnos_por_ciclo, catalogos, etc.');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('processed')->default(0)->comment('Registros procesados');
            $table->unsignedInteger('total')->default(0)->comment('Total estimado');
            $table->unsignedInteger('created_count')->default(0)->comment('Inserts realizados');
            $table->unsignedInteger('updated_count')->default(0)->comment('Updates realizados');
            $table->unsignedInteger('deleted_count')->default(0)->comment('Deletes (huérfanos)');
            $table->text('error_message')->nullable();
            $table->json('log')->nullable()->comment('Log detallado por tabla');
            $table->json('options')->nullable()->comment('Opciones: delete_orphans, solo_ciclo, etc.');
            $table->timestamps();

            $table->index(['operation', 'status'], 'idx_firebird_syncs_op_status');
            $table->index('ciclo', 'idx_firebird_syncs_ciclo');
            $table->index('created_at', 'idx_firebird_syncs_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firebird_syncs');
    }
};
