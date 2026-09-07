<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnos_niveles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();

            $table->unsignedInteger('numero_alumno')->comment('FK alumnos.numero_alumno');
            $table->unsignedInteger('inicial')->comment('Ciclo inicial');
            $table->unsignedInteger('final')->comment('Ciclo final');
            $table->unsignedTinyInteger('periodo')->comment('Ciclo periodo');
            $table->string('nivel', 10)->nullable()->comment('Nivel educativo');
            $table->unsignedTinyInteger('grado')->nullable()->comment('Grado');
            $table->char('status', 1)->nullable()->comment('Status del alumno en el nivel');

            $table->timestamps();

            $table->unique(
                ['numero_alumno', 'inicial', 'final', 'periodo'],
                'uk_alumnos_niveles_pk'
            );

            $table->foreign('numero_alumno')->references('numero_alumno')->on('alumnos')->cascadeOnDelete();

            $table->index(['inicial', 'final', 'periodo'], 'idx_alumnos_niveles_ciclo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos_niveles');
    }
};
