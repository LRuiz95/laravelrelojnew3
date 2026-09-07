<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cursos - Tabla origen: Firebird CURSOS
     * PK compuesta: INICIAL + FINAL + PERIODO + CLAVE_CURSO
     */
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            
            $table->unsignedInteger('inicial');
            $table->unsignedInteger('final');
            $table->unsignedTinyInteger('periodo');
            $table->string('clave_curso', 20)->comment('Clave curso');
            
            $table->string('nombre_curso', 100)->comment('Nombre del curso');
            $table->string('nivel', 10)->nullable();
            $table->string('turno', 10)->nullable();
            $table->string('id_campus', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['inicial', 'final', 'periodo', 'clave_curso'], 'uk_cursos_pk_compuesta');
            
            $table->index(['inicial', 'final', 'periodo', 'activo'], 'idx_cursos_ciclo_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
