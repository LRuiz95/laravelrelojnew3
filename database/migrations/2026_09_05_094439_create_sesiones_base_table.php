<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sesiones base (horario base por nivel/turno) - Tabla origen: Firebird CFGSESIONES
     */
    public function up(): void
    {
        Schema::create('sesiones_base', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            
            // PK compuesta lógica
            $table->string('nivel', 10)->comment('Nivel (MS, SU, etc.)');
            $table->string('turno', 10)->comment('Turno (MA, VE, etc.)');
            $table->unsignedTinyInteger('sesion')->comment('Número de sesión (1, 2, 3...)');
            
            $table->time('hora_inicio')->comment('Hora inicio sesión');
            $table->time('hora_fin')->comment('Hora fin sesión');
            $table->boolean('receso')->default(false)->comment('Es hora de receso/comida');
            $table->string('descripcion', 50)->nullable()->comment('Descripción (Ej: 1ra hora, Receso, etc.)');
            $table->unsignedTinyInteger('orden')->default(0)->comment('Orden en el día');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['nivel', 'turno', 'sesion'], 'uk_sesiones_base_pk_compuesta');
            $table->index(['nivel', 'turno', 'activo', 'orden'], 'idx_sesiones_base_nivel_turno_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_base');
    }
};
