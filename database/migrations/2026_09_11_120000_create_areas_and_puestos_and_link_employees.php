<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('identificador', 50)->unique()->comment('Identificador único del área');
            $table->string('descripcion', 150)->nullable()->comment('Descripción del área');
            $table->unsignedBigInteger('empleado_responsable_id')->nullable()->comment('Empleado responsable del área');
            $table->timestamps();

            $table->foreign('empleado_responsable_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });

        Schema::create('puestos', function (Blueprint $table) {
            $table->id();
            $table->string('identificador', 50)->unique()->comment('Identificador único del puesto');
            $table->string('descripcion', 150)->nullable()->comment('Descripción del puesto');
            $table->unsignedBigInteger('area_id')->nullable()->comment('Área a la que pertenece el puesto');
            $table->timestamps();

            $table->foreign('area_id')
                ->references('id')
                ->on('areas')
                ->nullOnDelete();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('id_campus')->constrained('areas')->nullOnDelete();
            $table->foreignId('puesto_id')->nullable()->after('area_id')->constrained('puestos')->nullOnDelete();
        });

        Schema::table('profesores', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('departamento')->constrained('areas')->nullOnDelete();
            $table->foreignId('puesto_id')->nullable()->after('area_id')->constrained('puestos')->nullOnDelete();
            $table->foreignId('director_id')->nullable()->after('puesto_id')->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('profesores', function (Blueprint $table) {
            $table->dropForeign(['director_id']);
            $table->dropForeign(['puesto_id']);
            $table->dropForeign(['area_id']);
            $table->dropColumn(['director_id', 'puesto_id', 'area_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['puesto_id']);
            $table->dropForeign(['area_id']);
            $table->dropColumn(['puesto_id', 'area_id']);
        });

        Schema::dropIfExists('puestos');
        Schema::dropIfExists('areas');
    }
};
