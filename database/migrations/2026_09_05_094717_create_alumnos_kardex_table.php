<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Alumnos_Kardex (calificaciones) - Tabla origen: Firebird ALUMNOS_KARDEX
     * PK compuesta: NUMEROALUMNO + INICIAL + FINAL + PERIODO + CLAVEASIGNATURA + ID_EVAL
     */
    public function up(): void
    {
        Schema::create('alumnos_kardex', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            
            $table->unsignedInteger('numero_alumno')->comment('FK alumnos.numero_alumno');
            $table->unsignedInteger('inicial')->comment('Ciclo inicial');
            $table->unsignedInteger('final')->comment('Ciclo final');
            $table->unsignedTinyInteger('periodo')->comment('Ciclo periodo');
            $table->string('clave_asignatura', 20)->comment('FK materias.clave_asignatura');
            $table->string('id_eval', 10)->comment('FK metodos_eval.id_eval');
            
            $table->decimal('calificacion', 5, 2)->nullable()->comment('Calificación numérica');
            $table->string('literal', 2)->nullable()->comment('S=Satisfactorio, N=No satisfactorio');
            $table->unsignedTinyInteger('tipo_examen')->default(0)->comment('Tipo examen');
            $table->date('fecha_examen')->nullable();
            $table->string('profesor', 50)->nullable()->comment('Profesor que calificó');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(
                ['numero_alumno', 'inicial', 'final', 'periodo', 'clave_asignatura', 'id_eval'],
                'uk_alumnos_kardex_pk_compuesta'
            );
            
            // FKs
            $table->foreign('numero_alumno')->references('numero_alumno')->on('alumnos')->cascadeOnDelete();
            $table->foreign('clave_asignatura')->references('clave_asignatura')->on('materias');
            $table->foreign('id_eval')->references('id_eval')->on('metodos_eval');
            
            $table->index(['inicial', 'final', 'periodo'], 'idx_alumnos_kardex_ciclo');
            $table->index(['numero_alumno', 'inicial', 'final', 'periodo'], 'idx_alumnos_kardex_alumno_ciclo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos_kardex');
    }
};
