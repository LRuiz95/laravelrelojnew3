<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Contratos - Tabla origen: Firebird EMPLEADOS_CONTRATOS_CAT / PROFESORES.CONTRATO
     */
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('contrato', 20)->unique()->comment('Clave contrato (ej: BASE, EVENTUAL, HONORARIOS, POR HORAS)');
            $table->string('descripcion', 100)->comment('Descripción tipo contrato');
            $table->enum('tipo_personal', ['admin', 'docente', 'ambos'])->default('ambos')->comment('Aplica a: admin, docente, ambos');
            $table->boolean('tiene_antiguedad')->default(false);
            $table->boolean('tiene_prestaciones')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['tipo_personal', 'activo'], 'idx_contratos_tipo_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
