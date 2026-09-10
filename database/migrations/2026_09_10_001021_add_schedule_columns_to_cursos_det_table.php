<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar columnas de horarios desde Firebird CURSOS_DET.
     * Firebird CURSOS_DET tiene: DIA, HORA_INICIAL, HORA_FINAL, ID_CAMPUS, EDIFICIO, AULA
     */
    public function up(): void
    {
        // Hacer clave_asignatura nullable (Firebird CURSOS_DET no tiene esta columna)
        Schema::table('cursos_det', function (Blueprint $table) {
            $table->string('clave_asignatura', 20)->nullable()->change();
        });

        Schema::table('cursos_det', function (Blueprint $table) {
            $table->unsignedTinyInteger('dia')->nullable()->comment('1=Lun, 2=Mar, 3=Mié, 4=Jue, 5=Vie, 6=Sáb, 7=Dom');
            $table->string('hora_inicial', 50)->nullable()->comment('Hora inicio (ej: 07:00)');
            $table->string('hora_final', 50)->nullable()->comment('Hora fin (ej: 08:30)');
            $table->string('id_campus', 20)->nullable()->comment('FK sedes.id_campus');
            $table->string('edificio', 20)->nullable()->comment('Edificio');
            $table->string('aula', 20)->nullable()->comment('Aula');

            $table->unique(['curso_id', 'dia', 'hora_inicial'], 'uk_cursos_det_horario');
            $table->index(['dia', 'hora_inicial'], 'idx_cursos_det_dia_hora');
            $table->index('id_campus', 'idx_cursos_det_campus');
        });
    }

    public function down(): void
    {
        Schema::table('cursos_det', function (Blueprint $table) {
            $table->dropIndex('idx_cursos_det_dia_hora');
            $table->dropIndex('idx_cursos_det_campus');
            $table->dropColumn(['dia', 'hora_inicial', 'hora_final', 'id_campus', 'edificio', 'aula']);
        });
    }
};
