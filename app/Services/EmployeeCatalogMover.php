<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Migra los empleados legados (device_id + metadata de hardware en la misma
 * fila) al esquema de catálogo central + tabla pivote device_employee.
 *
 * Es invocado desde la migración DML move_employee_data_to_central_catalog
 * y desde el comando artisan migrate:employees-to-central, por lo que debe
 * ser idempotente: si las columnas legadas ya no existen, no escribe nada.
 *
 * Se usa el query builder (DB::table) y no modelos Eloquent a propósito:
 * este código corre dentro de la migración, cuando la base todavía tiene las
 * columnas legadas pero los modelos ya están refactorizados al catálogo.
 *
 * Reglas:
 * - Dedupe por user_id conservando el registro más antiguo (menor id).
 * - Asistencias y huellas se re-vinculan al empleado conservado.
 * - La metadata de hardware se copia a la pivote tal cual estaba
 *   (password viaja cifrada, igual que en la columna legada).
 * - fingerprint_count inicial = total de huellas del empleado (la atribución
 *   por dispositivo se pierde con los datos legados; futuras extracciones
 *   mantienen el conteo exacto por dispositivo).
 * - Todo el trabajo pesado usa chunkById(500) para no agotar memoria con
 *   catálogos grandes.
 */
class EmployeeCatalogMover
{
    public const CHUNK_SIZE = 500;

    /**
     * @param  bool  $dryRun  Cuando es true solo calcula y devuelve el resumen,
     *                        sin modificar datos.
     * @return array{status: string, employees: int, duplicates_merged: int, pivots_created: int,
     *               attendances_repointed: int, fingerprints_moved: int, fingerprints_dropped: int,
     *               cards_nulled: int, uid_conflicts: int}
     */
    public function run(bool $dryRun = false): array
    {
        $summary = [
            'status' => 'migrated',
            'employees' => 0,
            'duplicates_merged' => 0,
            'pivots_created' => 0,
            'attendances_repointed' => 0,
            'fingerprints_moved' => 0,
            'fingerprints_dropped' => 0,
            'cards_nulled' => 0,
            'uid_conflicts' => 0,
        ];

        if (! $this->hasLegacyColumns()) {
            $summary['status'] = 'already_migrated';

            return $summary;
        }

        // ── Paso 1: fusionar duplicados por user_id (keeper = menor id). ──
        $duplicateUserIds = DB::table('employees')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $rows = DB::table('employees')
                ->where('user_id', $userId)
                ->orderBy('id')
                ->get();

            $keeper = $rows->shift();
            $summary['employees']++;

            foreach ($rows as $duplicate) {
                if (! $dryRun) {
                    $summary['attendances_repointed'] += DB::table('attendances')
                        ->where('employee_id', $duplicate->id)
                        ->update(['employee_id' => $keeper->id]);

                    [$moved, $dropped] = $this->moveFingerprints((int) $duplicate->id, (int) $keeper->id);
                    $summary['fingerprints_moved'] += $moved;
                    $summary['fingerprints_dropped'] += $dropped;

                    DB::table('employees')->where('id', $duplicate->id)->delete();
                }

                $summary['duplicates_merged']++;
            }
        }

        // ── Paso 2: crear filas pivote con la metadata de hardware. ──
        // fingerprint_counts se calcula una sola vez (employee_id => total)
        // para no recontar por cada enrolamiento.
        $fingerprintCounts = DB::table('fingerprints')
            ->selectRaw('employee_id, COUNT(*) as total')
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        DB::table('employees')
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($employees) use ($dryRun, &$summary, $fingerprintCounts): void {
                foreach ($employees as $row) {
                    $summary['employees']++;

                    if ($dryRun || $row->device_id === null) {
                        continue;
                    }

                    $deviceUid = (int) $row->uid;

                    if ($this->pivotExists((int) $row->device_id, $deviceUid)) {
                        // UID ocupado por otro empleado dentro del mismo equipo:
                        // dato sucio imposible de representar bajo el único
                        // [device_id, device_uid]; se conserva el catálogo sin
                        // vínculo y queda registrado para revisión manual.
                        $summary['uid_conflicts']++;

                        continue;
                    }

                    $cardNumber = $row->card_no;
                    if ($cardNumber !== null && $this->cardTaken($row->device_id, $cardNumber)) {
                        // Misma tarjeta asignada a dos personas del mismo equipo:
                        // se anula en la pivote para respetar el único compuesto.
                        $cardNumber = null;
                        $summary['cards_nulled']++;
                    }

                    DB::table('device_employee')->insert([
                        'device_id' => $row->device_id,
                        'employee_id' => $row->id,
                        'device_uid' => $deviceUid,
                        'role' => (int) $row->role,
                        'card_number' => $cardNumber,
                        'password' => $row->password,
                        'active' => (bool) $row->active,
                        'fingerprint_count' => (int) ($fingerprintCounts[$row->id] ?? 0),
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]);

                    $summary['pivots_created']++;
                }
            });

        return $summary;
    }

    public function hasLegacyColumns(): bool
    {
        return Schema::hasColumn('employees', 'card_no')
            && Schema::hasColumn('employees', 'uid')
            && Schema::hasColumn('employees', 'device_id');
    }

    /**
     * Envuelve run() en una transacción y registra fallos. Usada por el
     * comando artisan; la migración DML gestiona su propia transacción.
     */
    public function runInTransaction(bool $dryRun = false): array
    {
        try {
            return DB::transaction(fn (): array => $this->run($dryRun));
        } catch (Throwable $exception) {
            Log::error('Migración al catálogo central falló: '.$exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Mueve las huellas del duplicado hacia el keeper. Si el keeper ya tiene
     * esa huella se elimina la del duplicado (se conserva la más antigua).
     *
     * @return array{0: int, 1: int} [movidas, eliminadas]
     */
    private function moveFingerprints(int $fromEmployeeId, int $toEmployeeId): array
    {
        $moved = 0;
        $dropped = 0;

        DB::table('fingerprints')
            ->where('employee_id', $fromEmployeeId)
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($fingerprints) use (&$moved, &$dropped, $toEmployeeId): void {
                foreach ($fingerprints as $fingerprint) {
                    $conflictExists = DB::table('fingerprints')
                        ->where('employee_id', $toEmployeeId)
                        ->where('finger', $fingerprint->finger)
                        ->exists();

                    if ($conflictExists) {
                        DB::table('fingerprints')->where('id', $fingerprint->id)->delete();
                        $dropped++;

                        continue;
                    }

                    DB::table('fingerprints')
                        ->where('id', $fingerprint->id)
                        ->update(['employee_id' => $toEmployeeId]);
                    $moved++;
                }
            });

        return [$moved, $dropped];
    }

    private function pivotExists(int $deviceId, int $deviceUid): bool
    {
        return DB::table('device_employee')
            ->where('device_id', $deviceId)
            ->where('device_uid', $deviceUid)
            ->exists();
    }

    private function cardTaken(int $deviceId, string $cardNumber): bool
    {
        return DB::table('device_employee')
            ->where('device_id', $deviceId)
            ->where('card_number', $cardNumber)
            ->exists();
    }
}
