<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campo ignored_at permite marcar un sobrante como revisado/ignorado
     * permanentemente. Un sobrante ignorado:
     * - NO cuenta para la alerta principal del dashboard
     * - SÍ sigue visible en la pestaña "Sobrantes" con filtro explícito
     * - NO se elimina del dispositivo
     * - Es reversible (se puede quitar ignored_at)
     */
    public function up(): void
    {
        Schema::table('device_employee', function (Blueprint $table): void {
            $table->timestamp('ignored_at')->nullable()->after('fingerprint_count');
        });
    }

    public function down(): void
    {
        Schema::table('device_employee', function (Blueprint $table): void {
            $table->dropColumn('ignored_at');
        });
    }
};
