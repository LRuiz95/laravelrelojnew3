<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('auth_user_id')->nullable()->after('puesto_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('profesores', function (Blueprint $table) {
            $table->foreignId('auth_user_id')->nullable()->after('telefono')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['auth_user_id']);
            $table->dropColumn('auth_user_id');
        });

        Schema::table('profesores', function (Blueprint $table) {
            $table->dropForeign(['auth_user_id']);
            $table->dropColumn('auth_user_id');
        });
    }
};
