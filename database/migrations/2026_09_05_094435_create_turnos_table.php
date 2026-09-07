<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Turnos - Tabla origen: Firebird CFGTURNOS
     */
    public function up(): void
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('turno', 10)->unique()->comment('Clave turno (ej: MA, VE, M2, V2, etc.)');
            $table->string('descripcion', 100)->comment('Descripción (Matutino, Vespertino, etc.)');
            $table->string('descripcion_corta', 20)->nullable()->comment('Abreviatura (M, V, M2, V2)');
            $table->time('hora_inicio')->nullable()->comment('Hora inicio turno');
            $table->time('hora_fin')->nullable()->comment('Hora fin turno');
            $table->unsignedTinyInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['activo', 'orden'], 'idx_turnos_activo_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};
