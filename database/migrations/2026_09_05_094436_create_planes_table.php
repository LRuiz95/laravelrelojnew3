<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Planes de estudio - Tabla origen: Firebird CFGPLANES_MST / CFGPLANES_DET
     */
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedInteger('id_plan')->unique()->comment('ID plan (clave numérica)');
            $table->string('nombre_plan', 100)->comment('Nombre del plan');
            $table->string('nivel', 10)->nullable()->comment('Nivel: MS, SU, etc.');
            $table->string('modalidad', 20)->nullable()->comment('Modalidad: Escolarizada, Mixta, etc.');
            $table->unsignedInteger('duracion_semestres')->nullable()->comment('Duración en semestres');
            $table->boolean('activo')->default(true)->comment('Plan vigente');
            $table->timestamps();
            $table->index(['nivel', 'activo'], 'idx_planes_nivel_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};
