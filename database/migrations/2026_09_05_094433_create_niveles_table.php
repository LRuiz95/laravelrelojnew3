<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Niveles educativos - Tabla origen: Firebird CFGNIVELES
     */
    public function up(): void
    {
        Schema::create('niveles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('nivel', 10)->unique()->comment('Clave nivel (ej: MS, SU, DO)');
            $table->string('descripcion', 100)->comment('Descripción (Media Superior, Superior, Doctorado)');
            $table->unsignedTinyInteger('orden')->default(0)->comment('Orden de visualización');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['activo', 'orden'], 'idx_niveles_activo_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niveles');
    }
};
