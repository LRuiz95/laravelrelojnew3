<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ADR-001: Attendance unification - single Attendance model with attendance_type + source
     * Adds manual/class fields (nullable, only used when attendance_type != biometric)
     * Updates unique index to include attendance_type
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Tipo de asistencia: biometric (ZKTeco), manual_admin (checada manual admin), manual_teacher (checada manual docente), class (asistencia de clases)
            $table->enum('attendance_type', ['biometric', 'manual_admin', 'manual_teacher', 'class'])
                ->default('biometric')
                ->after('type')
                ->comment('Origen del registro: biometric=ZKTeco, manual_admin=checada admin, manual_teacher=checada docente, class=asistencia clases');

            // Fuente: zkteco (dispositivo biométrico), manual (captura manual), class (grid asistencia clases)
            $table->enum('source', ['zkteco', 'manual', 'class'])
                ->default('zkteco')
                ->after('attendance_type')
                ->comment('Fuente del dato: zkteco=sincronización dispositivo, manual=captura humana, class=asistencia clases');

            // Campos para asistencias manuales/clase (nullable - solo cuando attendance_type != biometric)
            $table->time('hora_entrada')->nullable()->after('source')
                ->comment('Hora entrada (manual_admin, manual_teacher, class)');
            $table->time('hora_salida')->nullable()->after('hora_entrada')
                ->comment('Hora salida (manual_admin, manual_teacher, class)');
            $table->time('hora_salida_comer')->nullable()->after('hora_salida')
                ->comment('Hora salida a comer (manual_admin)');
            $table->time('hora_regreso_comer')->nullable()->after('hora_salida_comer')
                ->comment('Hora regreso de comer (manual_admin)');

            // Índices para filtros frecuentes
            $table->index(['attendance_type', 'source'], 'idx_attendances_type_source');
            $table->index('recorded_at', 'idx_attendances_recorded_at');
        });

// Actualizar índice único compuesto: (employee_id, recorded_at, device_id, attendance_type)
            // El índice único original era att_unique_punch (device_id, user_id, recorded_at, state)
            // Lo recreamos incluyendo attendance_type y employee_id para permitir mismo empleado/hora/dispositivo con distinto tipo
            DB::statement('ALTER TABLE attendances DROP INDEX att_unique_punch');
            DB::statement('ALTER TABLE attendances ADD UNIQUE INDEX att_emp_rec_dev_type_unique (employee_id, recorded_at, device_id, attendance_type)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
// Restaurar índice único original
            DB::statement('ALTER TABLE attendances DROP INDEX att_emp_rec_dev_type_unique');
            DB::statement('ALTER TABLE attendances ADD UNIQUE INDEX att_unique_punch (device_id, user_id, recorded_at, state)');

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_type_source');
            $table->dropIndex('idx_attendances_recorded_at');
            $table->dropColumn([
                'attendance_type',
                'source',
                'hora_entrada',
                'hora_salida',
                'hora_salida_comer',
                'hora_regreso_comer',
            ]);
        });
    }
};
