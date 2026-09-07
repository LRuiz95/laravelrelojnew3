<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cursos_Det (detalle de materias por curso) - Tabla origen: Firebird CURSOS_DET
     */
    public function up(): void
    {
        Schema::create('cursos_det', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            
            $table->unsignedBigInteger('curso_id')->comment('FK cursos.id');
            $table->string('clave_asignatura', 20);
            
            $table->unsignedTinyInteger('semestre')->nullable();
            $table->unsignedSmallInteger('horas_teoria')->default(0);
            $table->unsignedSmallInteger('horas_practica')->default(0);
            $table->enum('tipo', ['obligatoria', 'optativa'])->default('obligatoria');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['curso_id', 'clave_asignatura'], 'uk_cursos_det_curso_asignatura');
            $table->foreign('clave_asignatura')->references('clave_asignatura')->on('materias');
            
            $table->index(['clave_asignatura', 'activo'], 'idx_cursos_det_asignatura_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos_det');
    }
};