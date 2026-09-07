<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Materias/Asignaturas - Tabla origen: Firebird CFGPLANES_DET
     */
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('clave_asignatura', 20)->comment('Clave asignatura (PK lógica)');
            $table->unsignedInteger('id_plan')->comment('FK a planes.id_plan');
            $table->string('nombre_asignatura', 150)->comment('Nombre completo');
            $table->string('nombre_corto', 50)->nullable()->comment('Nombre abreviado');
            $table->unsignedTinyInteger('semestre')->nullable()->comment('Semestre en que se cursa');
            $table->unsignedSmallInteger('horas_teoria')->default(0)->comment('Horas teoría semanales');
            $table->unsignedSmallInteger('horas_practica')->default(0)->comment('Horas práctica semanales');
            $table->unsignedSmallInteger('creditos')->default(0);
            $table->enum('tipo', ['obligatoria', 'optativa', 'electiva'])->default('obligatoria');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['clave_asignatura', 'id_plan'], 'uk_materias_plan_clave');
            $table->index(['id_plan', 'activa'], 'idx_materias_plan_activa');
            $table->index('semestre', 'idx_materias_semestre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};
