<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('slug');
            $table->string('action')->default('view');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['module_id', 'slug']);
            $table->index(['module_id', 'action']);
        });

        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('permission_group_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_group_id')->constrained('permission_groups')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['permission_group_id', 'permission_id'], 'pgp_group_permission_unique');
        });

        Schema::create('employee_permission_groups', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('permission_group_id')->constrained('permission_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['employee_id', 'permission_group_id']);
        });

        Schema::create('profesor_permission_groups', function (Blueprint $table) {
            $table->string('profesor_clave_profesor', 20);
            $table->foreignId('permission_group_id')->constrained('permission_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('profesor_clave_profesor')
                ->references('clave_profesor')
                ->on('profesores')
                ->cascadeOnDelete();

            $table->primary(['profesor_clave_profesor', 'permission_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesor_permission_groups');
        Schema::dropIfExists('employee_permission_groups');
        Schema::dropIfExists('permission_group_permissions');
        Schema::dropIfExists('permission_groups');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('modules');
    }
};
