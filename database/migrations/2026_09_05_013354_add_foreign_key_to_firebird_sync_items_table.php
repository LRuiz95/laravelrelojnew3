<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FK separada para evitar problemas de tipo de columna
     */
    public function up(): void
    {
        // Verificar que ambas tablas existen y tienen los tipos correctos
        DB::statement('
            ALTER TABLE `firebird_sync_items` 
            ADD CONSTRAINT `firebird_sync_items_firebird_sync_id_foreign` 
            FOREIGN KEY (`firebird_sync_id`) 
            REFERENCES `firebird_syncs` (`id`) 
            ON DELETE CASCADE
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firebird_sync_items', function (Blueprint $table) {
            $table->dropForeign('firebird_sync_items_firebird_sync_id_foreign');
            $table->dropColumn('firebird_sync_id');
        });
    }
};
