<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sedes/Campus - Tabla origen: Firebird CFGSEDES
     */
    public function up(): void
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('id_campus', 20)->unique()->comment('Clave campus (ej: 01, 02, CENTRO)');
            $table->string('descripcion', 100)->comment('Nombre sede (ej: Campus Centro, Campus Norte)');
            $table->string('direccion', 200)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index('activo', 'idx_sedes_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sedes');
    }
};
