<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DELETE a FROM cursos_det a INNER JOIN cursos_det b ON b.id < a.id AND b.curso_id = a.curso_id AND b.inicial <=> a.inicial AND b.final <=> a.final AND b.periodo <=> a.periodo AND b.codigo_curso <=> a.codigo_curso AND b.dia <=> a.dia AND b.hora_inicial <=> a.hora_inicial AND b.hora_final <=> a.hora_final AND b.id_campus <=> a.id_campus AND b.edificio <=> a.edificio AND b.aula <=> a.aula');
        Schema::table('cursos_det', function (Blueprint $table) {
            $table->dropUnique('uk_cursos_det_horario_ubicacion');
            $table->unique([
                'curso_id', 'inicial', 'final', 'periodo', 'codigo_curso',
                'dia', 'hora_inicial', 'hora_final', 'id_campus', 'edificio', 'aula',
            ], 'uk_cursos_det_horario_ubicacion_ciclo');
        });
    }

    public function down(): void
    {
        Schema::table('cursos_det', function (Blueprint $table) {
            $table->dropUnique('uk_cursos_det_horario_ubicacion_ciclo');
            $table->unique([
                'curso_id', 'dia', 'hora_inicial', 'hora_final', 'id_campus', 'edificio', 'aula',
            ], 'uk_cursos_det_horario_ubicacion');
        });
    }
};
