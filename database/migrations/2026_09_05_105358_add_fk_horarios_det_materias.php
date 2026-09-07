<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE `horarios_det` 
            ADD CONSTRAINT `horarios_det_clave_asignatura_foreign` 
            FOREIGN KEY (`clave_asignatura`) 
            REFERENCES `materias` (`clave_asignatura`) 
            ON DELETE RESTRICT
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `horarios_det` DROP FOREIGN KEY `horarios_det_clave_asignatura_foreign`');
    }
};