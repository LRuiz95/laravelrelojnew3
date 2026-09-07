<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Grupos - Tabla origen: Firebird GRUPOS
     * PK compuesta: CODIGO_GRUPO + INICIAL + FINAL + PERIODO
     */
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            
            // PK compuesta lógica (Firebird)
            $table->string('codigo_grupo', 20)->comment('Código grupo (ej: 101, 202, 303)');
            $table->unsignedInteger('inicial')->comment('Año inicial ciclo');
            $table->unsignedInteger('final')->comment('Año final ciclo');
            $table->unsignedTinyInteger('periodo')->comment('Periodo ciclo');
            
            $table->unsignedTinyInteger('grado')->comment('Grado (1, 2, 3, 4, 5, 6)');
            $table->string('turno', 10)->comment('Turno (MA, VE, M2, V2, etc.)');
            $table->string('nivel', 10)->comment('Nivel (MS, SU, etc.)');
            $table->unsignedSmallInteger('inscritos')->default(0)->comment('Alumnos inscritos');
            $table->string('id_campus', 20)->nullable()->comment('Sede');
            $table->string('carrera', 50)->nullable()->comment('Carrera/Especialidad');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['codigo_grupo', 'inicial', 'final', 'periodo'], 'uk_grupos_pk_compuesta');
            
            // FKs
            $table->foreign(['inicial', 'final', 'periodo'], 'fk_grupos_ciclo')
                ->references(['inicial', 'final', 'periodo'])->on('ciclos');
            $table->foreign('nivel')->references('nivel')->on('niveles');
            $table->foreign('turno')->references('turno')->on('turnos');
            $table->foreign('id_campus')->references('id_campus')->on('sedes');
            
            $table->index(['inicial', 'final', 'periodo', 'activo'], 'idx_grupos_ciclo_activo');
            $table->index(['nivel', 'turno', 'activo'], 'idx_grupos_nivel_turno_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
