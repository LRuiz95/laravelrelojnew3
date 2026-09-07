<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Alumnos - Tabla origen: Firebird ALUMNOS
     */
    public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedInteger('numero_alumno')->unique()->comment('Número de control (PK lógica)');
            
            $table->string('paterno', 50)->comment('Apellido paterno');
            $table->string('materno', 50)->nullable()->comment('Apellido materno');
            $table->string('nombre', 50)->comment('Nombre(s)');
            $table->string('curp', 18)->nullable()->unique()->comment('CURP');
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['M', 'F'])->nullable();
            $table->string('estado_civil', 20)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('colonia', 100)->nullable();
            $table->string('ciudad', 50)->nullable();
            $table->string('estado', 50)->nullable();
            $table->string('cp', 10)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('lugar_nacimiento', 100)->nullable();
            $table->string('nacionalidad', 50)->nullable()->default('MEXICANA');
            
            // Datos académicos
            $table->string('nivel', 10)->nullable()->comment('Nivel actual');
            $table->string('turno', 10)->nullable()->comment('Turno actual');
            $table->string('id_campus', 20)->nullable()->comment('Sede');
            $table->string('carrera', 50)->nullable();
            $table->string('plan', 20)->nullable();
            
            $table->date('fecha_ingreso')->nullable();
            $table->enum('estatus', ['ACTIVO', 'BAJA', 'EGRESADO', 'TITULADO', 'IRREGULAR'])->default('ACTIVO');
            $table->string('tipo_ingreso', 20)->nullable()->comment('EXAMEN, PASE REGLADO, etc.');
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            
            $table->index(['estatus', 'nivel', 'turno'], 'idx_alumnos_estatus_nivel_turno');
            $table->index(['paterno', 'materno', 'nombre'], 'idx_alumnos_nombre_completo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
