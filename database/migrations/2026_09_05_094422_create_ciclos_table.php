<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ciclos escolares - PK compuesta (INICIAL, FINAL, PERIODO) + id surrogate
     * Tabla origen: Firebird CICLOS
     */
    public function up(): void
    {
        Schema::create('ciclos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            
            // PK compuesta lógica (única)
            $table->unsignedInteger('inicial')->comment('Año inicial (ej: 2025)');
            $table->unsignedInteger('final')->comment('Año final (ej: 2025)');
            $table->unsignedTinyInteger('periodo')->comment('Periodo: 1=Semestral, 2=Cuatrimestral, 3=Anual, etc.');
            
            $table->string('descripcion', 100)->nullable()->comment('Descripción del ciclo');
            $table->date('fecha_inicial')->nullable()->comment('Fecha inicio ciclo');
            $table->date('fecha_final')->nullable()->comment('Fecha fin ciclo');
            $table->boolean('activo')->default(true)->comment('Ciclo activo');
            
            $table->timestamps();

            // PK compuesta única (regla de negocio)
            $table->unique(['inicial', 'final', 'periodo'], 'uk_ciclos_pk_compuesta');
            $table->index(['activo', 'inicial'], 'idx_ciclos_activo_inicial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciclos');
    }
};
