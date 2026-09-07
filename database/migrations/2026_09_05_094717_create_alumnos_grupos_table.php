<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Alumnos_Grupos (pivot) - Tabla origen: Firebird ALUMNOS_GRUPOS
     * PK compuesta: NUMEROALUMNO + CODIGO_GRUPO + INICIAL + FINAL + PERIODO
     */
    public function up(): void
    {
        Schema::create('alumnos_grupos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            
            $table->unsignedInteger('numero_alumno')->comment('FK alumnos.numero_alumno');
            $table->string('codigo_grupo', 20)->comment('FK grupos.codigo_grupo');
            $table->unsignedInteger('inicial')->comment('Ciclo inicial');
            $table->unsignedInteger('final')->comment('Ciclo final');
            $table->unsignedTinyInteger('periodo')->comment('Ciclo periodo');
            
            $table->date('fecha_inscripcion')->nullable();
            $table->enum('estatus', ['INSCRITO', 'BAJA', 'CAMBIO_GRUPO', 'REINSCRITO'])->default('INSCRITO');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(
                ['numero_alumno', 'codigo_grupo', 'inicial', 'final', 'periodo'],
                'uk_alumnos_grupos_pk_compuesta'
            );
            
            // FKs
            $table->foreign('numero_alumno')->references('numero_alumno')->on('alumnos')->cascadeOnDelete();
            $table->foreign(['codigo_grupo', 'inicial', 'final', 'periodo'])
                ->references(['codigo_grupo', 'inicial', 'final', 'periodo'])->on('grupos')
                ->cascadeOnDelete();
            
            $table->index(['inicial', 'final', 'periodo', 'estatus'], 'idx_alumnos_grupos_ciclo_estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos_grupos');
    }
};
