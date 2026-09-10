<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Device;
use App\Models\Pivots\DeviceEmployee;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestión de sobrantes: device_employee sin employee válido
 * o con employee dado de baja (status_actual='B').
 *
 * Regla formal (RULE-SOBRANTE-001):
 * Un device_employee es SOBRANTE cuando:
 *   A) employee_id no existe en employees (huérfano)
 *   O
 *   B) employees.status_actual = 'B' (baja en Firebird)
 *
 * Diseño: UNA sola consulta con LEFT JOIN + CASE para clasificar tipo.
 * Sin UNION. Soporta filtros, búsqueda, ordenamiento y paginación sobre
 * la misma consulta base.
 */
class SobranteService
{
    /**
     * Columnas base del query de sobrantes.
     *
     * El LEFT JOIN sobre employees permite distinguir los dos tipos:
     * - employees.id IS NULL → Tipo A (huérfano, sin catálogo)
     * - employees.status_actual = 'B' → Tipo B (baja en Firebird)
     *
     * El CASE en SELECT calcula sobrante_type en la misma query.
     */
    private function baseSelect(): array
    {
        return [
            'device_employee.id as pivot_id',
            'device_employee.device_id',
            'device_employee.employee_id',
            'device_employee.device_uid',
            'device_employee.card_number',
            'device_employee.fingerprint_count',
            'device_employee.active',
            'device_employee.ignored_at',
            'device_employee.created_at',
            'device_employee.updated_at',
            // Columnas de employee (NULL si no existe)
            'employees.id as emp_id',
            'employees.user_id',
            'employees.name as emp_name',
            'employees.status_actual',
            // Columnas de device
            'devices.name as device_name',
            'devices.ip as device_ip',
            'devices.status as device_status',
            // Clasificación calculada
            DB::raw("CASE WHEN employees.id IS NULL THEN 'A' ELSE 'B' END as sobrante_type"),
            DB::raw("CASE WHEN employees.id IS NULL THEN 'NO EXISTE EN CATÁLOGO' ELSE 'BAJA EN FIREBIRD' END as sobrante_reason"),
        ];
    }

    /**
     * Query base de sobrantes.
     *
     * LEFT JOIN sobre employees permite cubrir ambos tipos en una sola query:
     * - employee_id NULL o FK rota → employees.id es NULL → Tipo A
     * - employee_id válido + status_actual='B' → Tipo B
     *
     * Soporta filtros, búsqueda, ordenamiento y paginación.
     */
    public function query(
        bool $includeIgnored = false,
        ?int $deviceId = null,
        ?string $type = null,
        ?string $search = null,
        string $orderBy = 'device_employee.id',
        string $orderDir = 'asc',
    ): \Illuminate\Database\Query\Builder {
        $query = DB::table('device_employee')
            ->select($this->baseSelect())
            ->leftJoin('employees', 'employees.id', '=', 'device_employee.employee_id')
            ->join('devices', 'devices.id', '=', 'device_employee.device_id');

        // Regla base: sobrantes son Tipo A OR Tipo B
        $query->where(function ($q) {
            $q->whereNull('employees.id')
              ->orWhere('employees.status_actual', '=', 'B');
        });

        // Filtro por ignorados
        if (! $includeIgnored) {
            $query->whereNull('device_employee.ignored_at');
        }

        // Filtro por dispositivo
        if ($deviceId !== null) {
            $query->where('device_employee.device_id', '=', $deviceId);
        }

        // Filtro por tipo (A o B)
        if ($type === 'A') {
            $query->whereNull('employees.id');
        } elseif ($type === 'B') {
            $query->where('employees.status_actual', '=', 'B');
        }

        // Búsqueda por nombre o user_id
        if ($search !== null && $search !== '') {
            $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($safeSearch) {
                $q->where('employees.name', 'like', "%{$safeSearch}%")
                  ->orWhere('employees.user_id', 'like', "%{$safeSearch}%");
            });
        }

        // Ordenamiento
        $allowed = [
            'device_employee.id',
            'device_employee.device_id',
            'device_employee.device_uid',
            'device_employee.card_number',
            'devices.name',
            'employees.name',
            'employees.user_id',
            'employees.status_actual',
        ];
        $column = in_array($orderBy, $allowed, true) ? $orderBy : 'device_employee.id';
        $direction = strtolower($orderDir) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($column, $direction);

        return $query;
    }

    /**
     * Obtiene los sobrantes como Collection.
     */
    public function get(bool $includeIgnored = false, ?int $deviceId = null, ?string $type = null, ?string $search = null): \Illuminate\Support\Collection
    {
        return $this->query($includeIgnored, $deviceId, $type, $search)->get();
    }

    /**
     * Obtiene el conteo de sobrantes por tipo.
     *
     * Reutiliza query() con COUNT para consistencia con la query principal.
     */
    public function getStats(?int $deviceId = null): array
    {
        $total = $this->query(false, $deviceId)->count();

        $typeA = $this->query(false, $deviceId, 'A')->count();

        $typeB = $this->query(false, $deviceId, 'B')->count();

        $ignored = DeviceEmployee::whereNotNull('ignored_at')
            ->when($deviceId, fn ($q) => $q->where('device_id', '=', $deviceId))
            ->count();

        return [
            'total' => $total,
            'type_a' => $typeA,
            'type_b' => $typeB,
            'ignored' => $ignored,
        ];
    }

    /**
     * Obtiene un sobrante específico por pivot_id.
     */
    public function findById(int $pivotId): ?object
    {
        return $this->query(true)
            ->where('device_employee.id', $pivotId)
            ->first();
    }

    /**
     * Marca un sobrante como ignorado.
     *
     * Identifica el registro por device_id + device_uid (no por id),
     * ya que el device_uid es la identidad local del usuario en el checador.
     */
    public function ignore(int $deviceId, int $deviceUid): bool
    {
        $exists = DB::table('device_employee')
            ->where('device_id', $deviceId)
            ->where('device_uid', $deviceUid)
            ->exists();
        if (! $exists) {
            return false;
        }

        DB::table('device_employee')
            ->where('device_id', $deviceId)
            ->where('device_uid', $deviceUid)
            ->update(['ignored_at' => now()]);

        return true;
    }

    /**
     * Revoca el estado ignorado de un sobrante.
     *
     * Identifica el registro por device_id + device_uid.
     */
    public function unignore(int $deviceId, int $deviceUid): bool
    {
        $exists = DB::table('device_employee')
            ->where('device_id', $deviceId)
            ->where('device_uid', $deviceUid)
            ->exists();
        if (! $exists) {
            return false;
        }

        DB::table('device_employee')
            ->where('device_id', $deviceId)
            ->where('device_uid', $deviceUid)
            ->update(['ignored_at' => null]);

        return true;
    }

    /**
     * Determina el tipo de sobrante de un DeviceEmployee dado.
     * Retorna 'A', 'B' o null (no es sobrante).
     */
    public function classify(DeviceEmployee $pivot): ?string
    {
        if ($pivot->employee) {
            if ($pivot->employee->status_actual === 'B') {
                return 'B';
            }
            return null;
        }

        if (is_null($pivot->employee_id)) {
            return 'A';
        }

        if (! Employee::where('id', $pivot->employee_id)->exists()) {
            return 'A';
        }

        return null;
    }

    /**
     * Elimina un sobrante: hardware + pivot, JAMÁS Employee.
     *
     * Tipo A (sin Employee):
     *   1. removeUserFromDevice() → hardware
     *   2. DeviceEmployee::delete() → pivot
     *
     * Tipo B (Employee existe, status='B'):
     *   1. removeUserFromDevice() → hardware
     *   2. $pivot->delete() → pivot únicamente
     *   3. Employee permanece intacto
     *
     * @param  int  $deviceId
     * @param  int  $deviceUid
     * @param  string  $type  'A' o 'B'
     * @return array{success: bool, message: string}
     */
    public function remove(int $deviceId, int $deviceUid, string $type, ?ZktecoService $zktecoService = null): array
    {
        if (! in_array($type, ['A', 'B'], true)) {
            return ['success' => false, 'message' => 'Tipo de sobrante inválido.'];
        }

        $device = Device::find($deviceId);
        if (! $device) {
            return ['success' => false, 'message' => 'Dispositivo no encontrado.'];
        }

        $service = $zktecoService ?? App::makeWith(ZktecoService::class, ['device' => $device]);

        // 1. Eliminar del hardware
        $hwResult = $service->removeUserFromDevice($deviceUid);
        if (! $hwResult) {
            Log::warning('Sobrante remove: hardware removal failed', [
                'device_id' => $deviceId,
                'device_uid' => $deviceUid,
                'type' => $type,
            ]);
            // Continuamos de todas formas para limpiar el pivot
            // (el dispositivo puede estar offline)
        }

        // 2. Eliminar pivot según tipo
        if ($type === 'A') {
            // Tipo A: Employee NO existe. Eliminar pivot directamente.
            $deleted = DeviceEmployee::where('device_id', $deviceId)
                ->where('device_uid', $deviceUid)
                ->delete();

            if ($deleted === 0) {
                return ['success' => false, 'message' => 'Pivot no encontrado.'];
            }

            Log::info('Sobrante Tipo A eliminado', [
                'device_id' => $deviceId,
                'device_uid' => $deviceUid,
                'hw_success' => $hwResult,
            ]);

            return [
                'success' => true,
                'message' => $hwResult
                    ? 'Eliminado del dispositivo y del catálogo local.'
                    : 'Eliminado del catálogo local (dispositivo no respondió).',
            ];
        }

        if ($type === 'B') {
            // Tipo B: Employee SÍ existe. Eliminar solo pivot.
            $pivot = DeviceEmployee::where('device_id', $deviceId)
                ->where('device_uid', $deviceUid)
                ->first();

            if (! $pivot) {
                return ['success' => false, 'message' => 'Pivot no encontrado.'];
            }

            $pivot->delete();

            // Employee SE CONSERVA. No se accede a $employee->delete().
            // No se hace $employee->devices()->detach().
            // La baja de Employee es operación explícita separada.

            Log::info('Sobrante Tipo B eliminado', [
                'device_id' => $deviceId,
                'device_uid' => $deviceUid,
                'employee_id' => $pivot->employee_id,
                'hw_success' => $hwResult,
            ]);

            return [
                'success' => true,
                'message' => $hwResult
                    ? 'Eliminado del dispositivo y la relación local. Employee se conserva.'
                    : 'Eliminado de la relación local (dispositivo no respondió). Employee se conserva.',
            ];
        }

        // Should never reach here if type is validated
        return ['success' => false, 'message' => 'Tipo de sobrante inválido.'];
    }
}
