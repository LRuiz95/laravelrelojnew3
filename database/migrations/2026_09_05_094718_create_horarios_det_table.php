<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Horarios_Det (clases) - Tabla origen: Firebird HORARIOS_DET
     * PK compuesta: INICIAL + FINAL + PERIODO + CODIGO_GRUPO + CLAVEPROFESOR + CLAVEASIGNATURA + DIA + SESION
     */
    public function up(): void
    {
        Schema::create('horarios_det', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            
            // PK compuesta (Firebird)
            $table->unsignedInteger('inicial');
            $table->unsignedInteger('final');
            $table->unsignedTinyInteger('periodo');
            $table->string('codigo_grupo', 20);
            $table->string('clave_profesor', 20);
            $table->string('clave_asignatura', 20);
            $table->unsignedTinyInteger('dia')->comment('1=Lun, 2=Mar, 3=Mié, 4=Jue, 5=Vie, 6=Sáb, 7=Dom');
            $table->unsignedTinyInteger('sesion')->comment('Número de sesión (1, 2, 3...)');
            
            $table->decimal('horas_teoria_practica', 4, 2)->default(0)->comment('Horas teoría/práctica');
            $table->string('id_campus', 20)->nullable()->comment('Sede');
            $table->string('edificio', 20)->nullable();
            $table->string('aula', 20)->nullable();
            $table->string('origen_horario', 10)->nullable()->comment('HD=PTC, CA=PA');
            $table->unsignedSmallInteger('horas_semanales')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(
                ['inicial', 'final', 'periodo', 'codigo_grupo', 'clave_profesor', 'clave_asignatura', 'dia', 'sesion'],
                'uk_horarios_det_pk_compuesta'
            );
            
            // FKs se crean en migraciones separadas
            $table->index(['inicial', 'final', 'periodo', 'activo'], 'idx_horarios_det_ciclo_activo');
            $table->index(['clave_profesor', 'dia', 'sesion'], 'idx_horarios_det_profesor_dia_sesion');
            $table->index(['codigo_grupo', 'dia', 'sesion'], 'idx_horarios_det_grupo_dia_sesion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_det');
    }
};