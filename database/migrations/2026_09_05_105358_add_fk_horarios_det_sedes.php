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
            ADD CONSTRAINT `horarios_det_id_campus_foreign` 
            FOREIGN KEY (`id_campus`) 
            REFERENCES `sedes` (`id_campus`) 
            ON DELETE SET NULL
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `horarios_det` DROP FOREIGN KEY `horarios_det_id_campus_foreign`');
    }
};