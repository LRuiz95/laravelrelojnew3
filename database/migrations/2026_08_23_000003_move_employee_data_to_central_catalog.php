<?php

use App\Services\EmployeeCatalogMover;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migración puramente DML: mueve los datos de las columnas legadas hacia
     * la tabla pivote device_employee y fusiona duplicados por user_id.
     *
     * Por separación estricta DDL/DML (los DROP de MySQL hacen commit
     * implícito y romperían la transacción), el borrado de columnas vive en
     * la migración siguiente drop_legacy_columns_from_employees.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            app(EmployeeCatalogMover::class)->run();
        });
    }

    /**
     * No reversible por sí sola: la fusión de duplicados no puede deshacerse.
     * La restauración best-effort del esquema legado vive en el down() de la
     * migración DDL posterior, que reconstruye columnas desde la pivote.
     */
    public function down(): void
    {
        //
    }
};
