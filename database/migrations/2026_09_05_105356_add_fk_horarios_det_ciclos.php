<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE `horarios_det` 
            ADD CONSTRAINT `horarios_det_inicial_final_periodo_foreign` 
            FOREIGN KEY (`inicial`, `final`, `periodo`) 
            REFERENCES `ciclos` (`inicial`, `final`, `periodo`) 
            ON DELETE CASCADE
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `horarios_det` DROP FOREIGN KEY `horarios_det_inicial_final_periodo_foreign`');
    }
};