<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            foreach (['matricula', 'matricula_oficial', 'grado', 'subnivel', 'celular', 'id_escuela'] as $column) {
                if (Schema::hasColumn('alumnos', $column)) continue;
                match ($column) {
                    'grado' => $table->unsignedSmallInteger($column)->nullable(),
                    default => $table->string($column, 50)->nullable(),
                };
            }
        });

        Schema::table('grupos', function (Blueprint $table) {
            if (! Schema::hasColumn('grupos', 'tipo_grupo')) $table->string('tipo_grupo', 20)->nullable();
            if (! Schema::hasColumn('grupos', 'cupo_maximo')) $table->unsignedSmallInteger('cupo_maximo')->nullable();
            if (! Schema::hasColumn('grupos', 'grupo')) $table->string('grupo', 20)->nullable();
            if (! Schema::hasColumn('grupos', 'clave_profesor_titular')) $table->string('clave_profesor_titular', 50)->nullable();
            if (! Schema::hasColumn('grupos', 'clave_profesor_suplente')) $table->string('clave_profesor_suplente', 50)->nullable();
            if (! Schema::hasColumn('grupos', 'ciclo_cerrado')) $table->boolean('ciclo_cerrado')->nullable();
        });

        Schema::table('cursos', function (Blueprint $table) {
            if (! Schema::hasColumn('cursos', 'id_plan')) $table->unsignedInteger('id_plan')->nullable();
            if (! Schema::hasColumn('cursos', 'id_tipoeval')) $table->string('id_tipoeval', 20)->nullable();
            if (! Schema::hasColumn('cursos', 'id_etapa')) $table->string('id_etapa', 20)->nullable();
            if (! Schema::hasColumn('cursos', 'clave_asignatura')) $table->string('clave_asignatura', 20)->nullable();
            if (! Schema::hasColumn('cursos', 'clave_profesor')) $table->string('clave_profesor', 50)->nullable();
            if (! Schema::hasColumn('cursos', 'cupo_maximo')) $table->unsignedSmallInteger('cupo_maximo')->nullable();
            if (! Schema::hasColumn('cursos', 'desde')) $table->date('desde')->nullable();
            if (! Schema::hasColumn('cursos', 'hasta')) $table->date('hasta')->nullable();
            if (! Schema::hasColumn('cursos', 'sesiones')) $table->unsignedSmallInteger('sesiones')->nullable();
            if (! Schema::hasColumn('cursos', 'inscritos')) $table->unsignedSmallInteger('inscritos')->nullable();
            if (! Schema::hasColumn('cursos', 'suplente')) $table->string('suplente', 50)->nullable();
        });

        Schema::table('cursos_det', function (Blueprint $table) {
            if (! Schema::hasColumn('cursos_det', 'id_escuela')) $table->string('id_escuela', 20)->nullable();
            if (! Schema::hasColumn('cursos_det', 'inicial')) $table->unsignedInteger('inicial')->nullable();
            if (! Schema::hasColumn('cursos_det', 'final')) $table->unsignedInteger('final')->nullable();
            if (! Schema::hasColumn('cursos_det', 'periodo')) $table->unsignedTinyInteger('periodo')->nullable();
            if (! Schema::hasColumn('cursos_det', 'codigo_curso')) $table->string('codigo_curso', 20)->nullable();
            if (! Schema::hasColumn('cursos_det', 'dia')) $table->unsignedTinyInteger('dia')->nullable();
            if (! Schema::hasColumn('cursos_det', 'hora_inicial')) $table->time('hora_inicial')->nullable();
            if (! Schema::hasColumn('cursos_det', 'hora_final')) $table->time('hora_final')->nullable();
            if (! Schema::hasColumn('cursos_det', 'id_campus')) $table->string('id_campus', 20)->nullable();
            if (! Schema::hasColumn('cursos_det', 'edificio')) $table->string('edificio', 20)->nullable();
            if (! Schema::hasColumn('cursos_det', 'aula')) $table->string('aula', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cursos_det', function (Blueprint $table) {
            $table->dropColumn(['id_escuela', 'inicial', 'final', 'periodo', 'codigo_curso', 'dia', 'hora_inicial', 'hora_final', 'id_campus', 'edificio', 'aula']);
        });
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropIndex('idx_cursos_ciclo_materia');
            $table->dropColumn(['id_plan', 'id_tipoeval', 'id_etapa', 'clave_asignatura', 'clave_profesor', 'cupo_maximo', 'desde', 'hasta', 'sesiones', 'inscritos', 'suplente']);
        });
        Schema::table('grupos', function (Blueprint $table) {
            $table->dropColumn(['tipo_grupo', 'cupo_maximo', 'grupo', 'clave_profesor_titular', 'clave_profesor_suplente', 'ciclo_cerrado']);
        });
        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropColumn(['matricula', 'matricula_oficial', 'grado', 'subnivel', 'celular', 'id_escuela']);
        });
    }
};
