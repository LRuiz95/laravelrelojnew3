<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Profesores/Docentes - Tabla origen: Firebird PROFESORES
     */
    public function up(): void
    {
        Schema::create('profesores', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('clave_profesor', 20)->unique()->comment('Clave profesor (PK lógica)');
            $table->string('nombre_profesor', 100)->comment('Nombre completo');
            $table->string('paterno', 50)->nullable();
            $table->string('materno', 50)->nullable();
            $table->string('departamento', 50)->nullable();
            $table->string('contrato', 20)->nullable()->comment('FK contratos.contrato');
            $table->char('status_actual', 1)->default('A')->comment('A=Activo, B=Baja');
            $table->string('origen_horario', 10)->nullable()->comment('HD=Hora Docente (PTC), CA=Carga Asignada (PA)');
            $table->date('fecha_ingreso')->nullable();
            $table->string('id_campus', 20)->nullable()->comment('FK sedes.id_campus');
            $table->string('nivel', 10)->nullable();
            $table->string('turno', 10)->nullable();
            $table->string('rfc', 13)->nullable()->unique();
            $table->string('curp', 18)->nullable()->unique();
            $table->string('email', 100)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->timestamps();

            $table->index(['status_actual', 'departamento'], 'idx_profesores_status_depto');
            $table->index(['origen_horario', 'status_actual'], 'idx_profesores_origen_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesores');
    }
};
