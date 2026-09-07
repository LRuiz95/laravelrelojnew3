<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ADR-001: Employee unification - single Employee model with type enum
     * Adds academia/RRHH fields (nullable, only used when type=admin|teacher)
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Tipo de empleado: biometric (checador ZKTeco), admin (personal administrativo), teacher (docente)
            $table->enum('type', ['biometric', 'admin', 'teacher'])
                ->default('biometric')
                ->after('name')
                ->comment('Origen del empleado: biometric=checador ZKTeco, admin=personal administrativo, teacher=docente');

            // Campos academia/RRHH (nullable - solo se usan según type)
            $table->string('numero_empleado', 50)->nullable()->unique()->after('type')
                ->comment('Clave empleado administrativo (EMPLEADOS.NUMEMPLEADO)');
            $table->string('clave_profesor', 50)->nullable()->unique()->after('numero_empleado')
                ->comment('Clave docente (PROFESORES.CLAVEPROFESOR)');
            $table->string('departamento', 100)->nullable()->after('clave_profesor')
                ->comment('Departamento (EMPLEADOS.DEPARTAMENTO / PROFESORES.DEPARTAMENTO)');
            $table->string('cargo', 100)->nullable()->after('departamento')
                ->comment('Cargo/puesto (EMPLEADOS.CARGO)');
            $table->string('contrato', 50)->nullable()->after('cargo')
                ->comment('Tipo de contrato (EMPLEADOS.CONTRATO / PROFESORES.CONTRATO)');
            $table->char('status_actual', 1)->nullable()->after('contrato')
                ->comment('Estatus: A=Activo, B=Baja (EMPLEADOS.STATUSACTUAL / PROFESORES.STATUSACTUAL)');
            $table->date('fecha_ingreso')->nullable()->after('status_actual')
                ->comment('Fecha de ingreso (EMPLEADOS.FECHA_INGRESO / PROFESORES.FECHA_INGRESO)');
            $table->string('id_campus', 20)->nullable()->after('fecha_ingreso')
                ->comment('Sede/Campus (EMPLEADOS.ID_CAMPUS / PROFESORES.ID_CAMPUS)');
            $table->string('nivel', 20)->nullable()->after('id_campus')
                ->comment('Nivel académico (EMPLEADOS.NIVEL / PROFESORES.NIVEL)');
            $table->string('tarjeta_id', 50)->nullable()->after('nivel')
                ->comment('ID de tarjeta RFID (EMPLEADOS.TARJETA_ID)');

            // Índices para consultas frecuentes
            $table->index(['type', 'status_actual'], 'idx_employees_type_status');
            $table->index('id_campus', 'idx_employees_campus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employees_type_status');
            $table->dropIndex('idx_employees_campus');
            $table->dropUnique(['numero_empleado']);
            $table->dropUnique(['clave_profesor']);
            $table->dropColumn([
                'type',
                'numero_empleado',
                'clave_profesor',
                'departamento',
                'cargo',
                'contrato',
                'status_actual',
                'fecha_ingreso',
                'id_campus',
                'nivel',
                'tarjeta_id',
            ]);
        });
    }
};
