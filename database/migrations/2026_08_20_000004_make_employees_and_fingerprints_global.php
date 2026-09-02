<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('employees')
                ->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_id')
                ->each(function (string $userId): void {
                    $employees = DB::table('employees')
                        ->where('user_id', $userId)
                        ->orderBy('id')
                        ->get();
                    $keeper = $employees->first();

                    foreach ($employees->skip(1) as $duplicate) {
                        DB::table('attendances')
                            ->where('employee_id', $duplicate->id)
                            ->update(['employee_id' => $keeper->id]);

                        DB::table('fingerprints')
                            ->where('employee_id', $duplicate->id)
                            ->get()
                            ->each(function (object $fingerprint) use ($keeper): void {
                                $exists = DB::table('fingerprints')
                                    ->where('employee_id', $keeper->id)
                                    ->where('finger', $fingerprint->finger)
                                    ->exists();

                                if ($exists) {
                                    DB::table('fingerprints')->where('id', $fingerprint->id)->delete();
                                } else {
                                    DB::table('fingerprints')
                                        ->where('id', $fingerprint->id)
                                        ->update(['employee_id' => $keeper->id]);
                                }
                            });

                        DB::table('employees')->where('id', $duplicate->id)->delete();
                    }
                });

            DB::table('fingerprints')
                ->select(['employee_id', 'finger'])
                ->groupBy(['employee_id', 'finger'])
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->each(function (object $group): void {
                    DB::table('fingerprints')
                        ->where('employee_id', $group->employee_id)
                        ->where('finger', $group->finger)
                        ->orderBy('id')
                        ->get()
                        ->skip(1)
                        ->each(fn (object $fingerprint) => DB::table('fingerprints')->where('id', $fingerprint->id)->delete());
                });
        });

        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'device_id')
                && ! $this->indexExists('employees', 'employees_device_id_index')) {
                $table->index('device_id');
            }
            if (Schema::hasColumn('employees', 'device_id')
                && $this->indexExists('employees', 'employees_device_id_user_id_unique')) {
                $table->dropUnique(['device_id', 'user_id']);
            }
            if (! $this->indexExists('employees', 'employees_user_id_unique')) {
                $table->unique('user_id');
            }
        });

        if (DB::getDriverName() === 'sqlite') {
            // SQLite no puede soltar por ALTER una columna que sirve de
            // índice-soporte a la FK hacia employees. Se reconstruye la tabla
            // SIN device_id para converger con el esquema resultante en MySQL;
            // si la columna quedara heredada como NOT NULL, migraciones
            // posteriores (2026_08_23_000002) no podrían re-crearla nullable.
            $this->rebuildFingerprintsTableWithoutDeviceForSqlite();
        } else {
            Schema::table('fingerprints', function (Blueprint $table): void {
                if (Schema::hasColumn('fingerprints', 'device_id')
                    && ! $this->indexExists('fingerprints', 'fingerprints_device_id_index')) {
                    $table->index('device_id');
                }
                if ($this->indexExists('fingerprints', 'fingerprints_device_id_employee_id_finger_unique')) {
                    $table->dropUnique(['device_id', 'employee_id', 'finger']);
                }
                if (Schema::hasColumn('fingerprints', 'device_id')) {
                    $table->dropForeign(['device_id']);
                    $table->dropColumn('device_id');
                }
                if (! $this->indexExists('fingerprints', 'fingerprints_employee_id_finger_unique')) {
                    $table->unique(['employee_id', 'finger']);
                }
            });
        }
    }

    /**
     * Reconstrucción de fingerprints para SQLite replicando el resultado de
     * esta migración en MySQL: sin device_id, único (employee_id, finger) y
     * el índice de template_hash reducido a una columna (MySQL reduce el
     * compuesto al soltar device_id; aquí conservamos su nombre histórico).
     */
    private function rebuildFingerprintsTableWithoutDeviceForSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            Schema::dropIfExists('fingerprints_rebuild');

            Schema::create('fingerprints_rebuild', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('finger')->comment('Dedo ZKTeco 0-9');
                $table->binary('template');
                $table->string('template_hash', 64);
                $table->timestamps();

                $table->unique(['employee_id', 'finger'], 'fingerprints_employee_id_finger_unique');
            });

            // El índice de template_hash se crea DESPUÉS del reemplazo: los
            // nombres de índice son globales en SQLite y el compuesto original
            // (mismo nombre) vive aún en la tabla vieja.
            DB::table('fingerprints')
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('fingerprints_rebuild')->insert([
                            'id' => $row->id,
                            'employee_id' => $row->employee_id,
                            'finger' => $row->finger,
                            'template' => $row->template,
                            'template_hash' => $row->template_hash,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ]);
                    }
                });

            Schema::dropIfExists('fingerprints');
            Schema::rename('fingerprints_rebuild', 'fingerprints');

            DB::statement(
                'CREATE INDEX "fingerprints_device_id_template_hash_index" ON "fingerprints" ("template_hash")'
            );
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        Schema::table('fingerprints', function (Blueprint $table): void {
            $table->dropUnique(['employee_id', 'finger']);
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->unique(['device_id', 'employee_id', 'finger']);
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
            $table->unique(['device_id', 'user_id']);
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
