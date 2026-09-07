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
     * FK separada para cursos_det
     */
    public function up(): void
    {
        DB::statement('
            ALTER TABLE `cursos_det` 
            ADD CONSTRAINT `cursos_det_curso_id_foreign` 
            FOREIGN KEY (`curso_id`) 
            REFERENCES `cursos` (`id`) 
            ON DELETE CASCADE
        ');
    }

    public function down(): void
    {
        Schema::table('cursos_det', function (Blueprint $table) {
            $table->dropForeign('cursos_det_curso_id_foreign');
            $table->dropColumn('curso_id');
        });
    }
};
