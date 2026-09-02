<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restaura device_id en fingerprints (existía en el esquema original y fue
     * retirado en 2026_08_20). Las filas legadas quedan con NULL = "origen
     * desconocido"; la primera re-extracción desde un checador crea su fila
     * por dispositivo sin tocar el histórico.
     */
    public function up(): void
    {
        Schema::table('fingerprints', function (Blueprint $table): void {
            if (! Schema::hasColumn('fingerprints', 'device_id')) {
                $table->foreignId('device_id')
                    ->nullable()
                    ->after('employee_id')
                    ->constrained()
                    ->nullOnDelete();
            }
        });

        // El único (employee_id, finger) es el índice soporte de la FK hacia
        // employees: soltarlo primero dispara el error 1553 de MySQL. Se crea
        // PRIMERO el nuevo único (también inicia por employee_id, así hereda
        // el soporte de la FK) y recién entonces se suelta el anterior. El
        // orden hace la migración reanudable si un intento previo quedó a medio.
        if (! $this->indexExists('fingerprints', 'fingerprints_employee_id_finger_device_id_unique')) {
            Schema::table('fingerprints', function (Blueprint $table): void {
                $table->unique(['employee_id', 'finger', 'device_id'], 'fingerprints_employee_id_finger_device_id_unique');
            });
        }

        if ($this->indexExists('fingerprints', 'fingerprints_employee_id_finger_unique')) {
            Schema::table('fingerprints', function (Blueprint $table): void {
                $table->dropUnique('fingerprints_employee_id_finger_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('fingerprints', 'fingerprints_employee_id_finger_device_id_unique')) {
            Schema::table('fingerprints', function (Blueprint $table): void {
                $table->dropUnique('fingerprints_employee_id_finger_device_id_unique');
            });
        }

        Schema::table('fingerprints', function (Blueprint $table): void {
            $table->unique(['employee_id', 'finger'], 'fingerprints_employee_id_finger_unique');
        });

        Schema::table('fingerprints', function (Blueprint $table): void {
            if (Schema::hasColumn('fingerprints', 'device_id') && DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['device_id']);
                $table->dropColumn('device_id');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'mysql') {
            return DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        }

        return collect(DB::select("PRAGMA index_list('{$table}')"))
            ->contains(fn (object $row): bool => ($row->name ?? '') === $index);
    }
};
