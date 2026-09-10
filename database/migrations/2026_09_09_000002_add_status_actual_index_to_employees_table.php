<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice para acelerar la consulta de sobrantes tipo B:
     * employees.status_actual = 'B'
     *
     * Sin este índice, la consulta LEFT JOIN + WHERE status_actual = 'B'
     * escanea toda la tabla de employees.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->index('status_actual', 'employees_status_actual_index');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropIndex('employees_status_actual_index');
        });
    }
};
