<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // La migración academia (2026_09_05_010214) ya eliminó att_unique_punch
            // y creó att_emp_rec_dev_type_unique. Eliminamos ese y creamos el correcto.
            $table->dropUnique('att_emp_rec_dev_type_unique');
            $table->unique(['device_id', 'employee_id', 'recorded_at'], 'attendance_device_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendance_device_employee_unique');
            // Restaurar el índice que creó la migración academia
            $table->unique(['employee_id', 'recorded_at', 'device_id', 'attendance_type'], 'att_emp_rec_dev_type_unique');
        });
    }
};