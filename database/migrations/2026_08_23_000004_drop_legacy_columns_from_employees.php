<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migración puramente DDL. SIN transacción a propósito: en MySQL/MariaDB
     * las sentencias DROP provocan commit implícito, así que envolverlas en
     * DB::transaction daría una falsa sensación de atomicidad.
     *
     * Convierte employees en catálogo central: elimina device_id y toda la
     * metadata de hardware (ya copiada a device_employee) y vuelve user_id
     * clave única global.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if ($this->indexExists('employees', 'employees_device_id_user_id_unique')) {
                $table->dropUnique('employees_device_id_user_id_unique');
            }

            if (! $this->indexExists('employees', 'employees_user_id_unique')) {
                $table->unique('user_id');
            }
        });

        // SQLite no puede soltar por ALTER una columna que participa en una
        // FK (ni puede soltar FKs); la tabla se reconstruye sin las columnas
        // legadas. MySQL sí soporta DROP FOREIGN KEY + DROP COLUMN.
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildEmployeesTable(central: true);

            return;
        }

        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'device_id')) {
                $table->dropForeign(['device_id']);
                $table->dropColumn('device_id');
            }
        });

        Schema::table('employees', function (Blueprint $table): void {
            foreach (['uid', 'role', 'card_no', 'password', 'active'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Restauración best-effort del esquema legado reconstruyendo las columnas
     * desde la pivote (primer enrolamiento de cada empleado). Los empleados
     * que quedaron sin enrolamiento reciben device_id NULL.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if ($this->indexExists('employees', 'employees_user_id_unique')) {
                $table->dropUnique('employees_user_id_unique');
            }
        });

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildEmployeesTable(central: false);
            $this->restoreLegacyAttributesFromPivot();

            return;
        }

        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'device_id')) {
                $table->foreignId('device_id')->nullable()->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('employees', 'uid')) {
                $table->unsignedInteger('uid')->default(0);
            }
            if (! Schema::hasColumn('employees', 'role')) {
                $table->unsignedTinyInteger('role')->default(0);
            }
            if (! Schema::hasColumn('employees', 'card_no')) {
                $table->string('card_no')->nullable();
            }
            if (! Schema::hasColumn('employees', 'password')) {
                $table->string('password')->nullable();
            }
            if (! Schema::hasColumn('employees', 'active')) {
                $table->boolean('active')->default(true);
            }
        });

        $this->restoreLegacyAttributesFromPivot();

        Schema::table('employees', function (Blueprint $table): void {
            if (! $this->indexExists('employees', 'employees_device_id_user_id_unique')) {
                $table->unique(['device_id', 'user_id']);
            }
        });
    }

    /**
     * Reconstrucción física de employees para SQLite:
     * - central=true  → esquema destino de catálogo (id, user_id único, name).
     * - central=false → esquema legado restaurado (FK device_id incluida).
     * Se desactivan temporalmente las FK para poder reemplazar una tabla que
     * es referenciada por attendances/fingerprints.
     */
    private function rebuildEmployeesTable(bool $central): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            Schema::dropIfExists('employees_rebuild');

            Schema::create('employees_rebuild', function (Blueprint $table) use ($central): void {
                $table->id();
                $table->string('name');

                if ($central) {
                    $table->string('user_id')->unique();
                } else {
                    $table->string('user_id');
                    $table->foreignId('device_id')->nullable()->constrained()->cascadeOnDelete();
                    $table->unsignedInteger('uid')->default(0);
                    $table->unsignedTinyInteger('role')->default(0);
                    $table->string('card_no')->nullable();
                    $table->string('password')->nullable();
                    $table->boolean('active')->default(true);
                }

                $table->timestamps();
            });

            if (! $central) {
                Schema::table('employees_rebuild', function (Blueprint $table): void {
                    $table->unique(['device_id', 'user_id'], 'employees_device_id_user_id_unique');
                });
            }

            DB::table('employees')->orderBy('id')->each(function (object $row) use ($central): void {
                $payload = [
                    'id' => $row->id,
                    'name' => $row->name,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];

                if ($central) {
                    $payload['user_id'] = $row->user_id;
                } else {
                    $payload['user_id'] = $row->user_id;
                    $payload['device_id'] = null;
                    $payload['uid'] = 0;
                    $payload['role'] = 0;
                    $payload['card_no'] = null;
                    $payload['password'] = null;
                    $payload['active'] = true;
                }

                DB::table('employees_rebuild')->insert($payload);
            });

            Schema::dropIfExists('employees');
            Schema::rename('employees_rebuild', 'employees');
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function restoreLegacyAttributesFromPivot(): void
    {
        DB::table('device_employee')
            ->orderBy('id')
            ->get()
            ->each(function (object $pivot): void {
                DB::table('employees')
                    ->where('id', $pivot->employee_id)
                    ->whereNull('card_no')
                    ->where('uid', 0)
                    ->update([
                        'device_id' => $pivot->device_id,
                        'uid' => $pivot->device_uid,
                        'role' => $pivot->role,
                        'card_no' => $pivot->card_number,
                        'password' => $pivot->password,
                        'active' => $pivot->active,
                    ]);
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
