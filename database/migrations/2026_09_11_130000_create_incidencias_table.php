<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->string('asunto', 150);
            $table->string('tipo_justificacion', 80);
            $table->date('fecha_justificacion');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->unsignedBigInteger('empleado_id')->nullable();
            $table->string('profesor_clave', 50)->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('puesto_id')->nullable();
            $table->unsignedBigInteger('director_id')->nullable();
            $table->string('numero_empleado', 50)->nullable();
            $table->text('motivo');
            $table->string('estado', 30)->default('pendiente');
            $table->unsignedBigInteger('responsable_area_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamp('autorizado_at')->nullable();
            $table->unsignedBigInteger('autorizado_por_user_id')->nullable();
            $table->timestamps();

            $table->foreign('empleado_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('area_id')->references('id')->on('areas')->nullOnDelete();
            $table->foreign('puesto_id')->references('id')->on('puestos')->nullOnDelete();
            $table->foreign('director_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('responsable_area_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('autorizado_por_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('profesor_clave')->references('clave_profesor')->on('profesores')->nullOnDelete();

            $table->index('estado');
            $table->index(['empleado_id', 'estado']);
            $table->index(['profesor_clave', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
