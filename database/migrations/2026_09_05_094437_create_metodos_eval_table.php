<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Métodos de evaluación - Tabla origen: Firebird CFGPLANES_EVAL / CFGPLANES_ETAPAS
     */
    public function up(): void
    {
        Schema::create('metodos_eval', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('id_eval', 10)->unique()->comment('ID evaluación (ej: A, B, C, D, E, F, G)');
            $table->string('nombre_corto', 20)->comment('Nombre corto (P1, P2, P3, CF, EXR, EXRS, CT)');
            $table->string('descripcion', 100)->comment('Descripción (Parcial 1, Parcial 2, Final, etc.)');
            $table->unsignedTinyInteger('tipo_examen')->default(0)->comment('Tipo: 0=Parcial, 1=Final, 2=Extraordinario, 3=Repetición');
            $table->unsignedTinyInteger('orden')->default(0)->comment('Orden en el ciclo');
            $table->boolean('es_final')->default(false)->comment('Es calificación final (CT)');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['activo', 'orden'], 'idx_metodos_eval_activo_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos_eval');
    }
};
