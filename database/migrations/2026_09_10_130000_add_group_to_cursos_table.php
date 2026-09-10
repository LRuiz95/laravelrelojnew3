<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('codigo_grupo', 50)->nullable()->after('clave_curso');
            $table->index(['inicial', 'final', 'periodo', 'codigo_grupo'], 'idx_cursos_ciclo_grupo');
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropIndex('idx_cursos_ciclo_grupo');
            $table->dropColumn('codigo_grupo');
        });
    }
};
