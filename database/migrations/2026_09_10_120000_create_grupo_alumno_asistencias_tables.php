<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos_asistencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('inicial');
            $table->unsignedInteger('final');
            $table->unsignedTinyInteger('periodo');
            $table->string('codigo_grupo', 50);
            $table->string('clave_profesor', 50);
            $table->string('clave_asignatura', 20);
            $table->unsignedTinyInteger('dia');
            $table->unsignedSmallInteger('sesion');
            $table->date('fecha');
            $table->string('observaciones', 1000)->nullable();
            $table->timestamps();

            $table->unique([
                'inicial', 'final', 'periodo', 'codigo_grupo', 'clave_profesor',
                'clave_asignatura', 'dia', 'sesion', 'fecha',
            ], 'uk_grupos_asistencias_clase');
            $table->index(['inicial', 'final', 'periodo', 'codigo_grupo', 'fecha'], 'idx_grupos_asistencias_contexto');
        });

        Schema::create('alumnos_asistencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('inicial');
            $table->unsignedInteger('final');
            $table->unsignedTinyInteger('periodo');
            $table->unsignedInteger('numero_alumno');
            $table->string('codigo_grupo', 50);
            $table->string('clave_profesor', 50);
            $table->string('clave_asignatura', 20);
            $table->unsignedTinyInteger('dia');
            $table->unsignedSmallInteger('sesion');
            $table->date('fecha');
            $table->enum('estado', ['PRESENTE', 'AUSENTE', 'RETARDO', 'JUSTIFICADO']);
            $table->string('observaciones', 500)->nullable();
            $table->timestamps();

            $table->unique([
                'inicial', 'final', 'periodo', 'numero_alumno', 'codigo_grupo',
                'clave_profesor', 'clave_asignatura', 'dia', 'sesion', 'fecha',
            ], 'uk_alumnos_asistencias_clase');
            $table->index(['inicial', 'final', 'periodo', 'codigo_grupo', 'fecha'], 'idx_alumnos_asistencias_contexto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos_asistencias');
        Schema::dropIfExists('grupos_asistencias');
    }
};
