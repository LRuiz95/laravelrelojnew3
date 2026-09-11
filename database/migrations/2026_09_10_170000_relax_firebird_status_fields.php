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
            $table->string('estatus', 30)->default('ACTIVO')->change();
        });

        Schema::table('grupos', function (Blueprint $table) {
            $table->boolean('ciclo_cerrado')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->enum('estatus', ['ACTIVO', 'BAJA', 'EGRESADO', 'TITULADO', 'IRREGULAR'])->default('ACTIVO')->change();
        });
    }
};
