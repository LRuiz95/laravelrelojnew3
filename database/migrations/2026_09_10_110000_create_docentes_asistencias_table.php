<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes_asistencias', function (Blueprint $table) {
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
            $table->enum('estado', ['PRESENTE', 'AUSENTE', 'RETARDO', 'JUSTIFICADO']);
            $table->string('observaciones', 500)->nullable();
            $table->timestamps();

            $table->unique([
                'inicial', 'final', 'periodo', 'codigo_grupo', 'clave_profesor',
                'clave_asignatura', 'dia', 'sesion', 'fecha',
            ], 'uk_docentes_asistencias_clase');
            $table->index(['inicial', 'final', 'periodo', 'fecha'], 'idx_docentes_asistencias_ciclo_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes_asistencias');
    }
};
