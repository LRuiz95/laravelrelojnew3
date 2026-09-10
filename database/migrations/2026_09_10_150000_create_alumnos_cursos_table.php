<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnos_cursos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('inicial');
            $table->unsignedInteger('final');
            $table->unsignedTinyInteger('periodo');
            $table->string('codigo_curso', 20);
            $table->unsignedInteger('numero_alumno');
            $table->string('status', 30)->nullable();
            $table->string('id_plan', 30)->nullable();
            $table->string('id_tipoeval', 30)->nullable();
            $table->string('id_etapa', 30)->nullable();
            $table->string('clave_asignatura', 20)->nullable();
            $table->string('version', 30)->nullable();
            $table->unsignedTinyInteger('tipoexamen')->nullable();
            $table->boolean('web')->nullable();
            $table->string('web_operacion', 30)->nullable();
            $table->timestamps();

            $table->unique(['inicial', 'final', 'periodo', 'codigo_curso', 'numero_alumno', 'id_tipoeval', 'id_etapa', 'clave_asignatura', 'version', 'tipoexamen'], 'uk_alumnos_cursos_origen');
            $table->index(['inicial', 'final', 'periodo', 'numero_alumno'], 'idx_alumnos_cursos_ciclo_alumno');
            $table->index(['inicial', 'final', 'periodo', 'codigo_curso'], 'idx_alumnos_cursos_ciclo_curso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos_cursos');
    }
};
