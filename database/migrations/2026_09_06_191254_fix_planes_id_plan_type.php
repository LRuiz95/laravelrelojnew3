<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->dropUnique('planes_id_plan_unique');
            $table->string('id_plan', 10)->unique()->change();
        });

        Schema::table('materias', function (Blueprint $table) {
            $table->string('id_plan', 10)->change();
        });
    }

    public function down(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->unsignedInteger('id_plan')->change();
        });

        Schema::table('planes', function (Blueprint $table) {
            $table->unsignedInteger('id_plan')->unique()->change();
        });
    }
};
