<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ZktecoConnectionException;
use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Fingerprint;
use App\Models\Pivots\DeviceEmployee;
use CodingLibs\ZktecoPhp\Exceptions\InvalidParamException;
use CodingLibs\ZktecoPhp\Libs\ZKTeco;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZktecoService
{
    /**
     * Reintentos de red: con internet inestable la primera falla no
     * significa que el checador esté caído. boot() reintenta el
     * ping/conexión inicial con backoff progresivo antes de rendirse.
     * Operaciones largas (sync de usuarios/asistencias/huellas) usan
     * MAX_RETRIES_LONG: mueven más datos por UDP, así que están más
     * expuestas a pérdida de paquetes y ameritan más margen.
     */
    private const MAX_RETRIES = 3;

    private const MAX_RETRIES_LONG = 5;

    private const RETRY_DELAY_MS = 800;

    /**
     * Timeout de socket (segundos) para el cliente ZKTeco. Con red
     * inestable, 5s corta respuestas que solo van lentas; se sube a 15s
     * como base. TIMEOUT_STEP es cuánto crece el timeout en cada
     * reintento (timeout adaptativo): si el intento 1 falla por lentitud
     * de red, el intento 2 no repite el mismo timeout corto, le da más
     * margen a la respuesta UDP de llegar. TIMEOUT_MAX evita que esto
     * crezca sin límite en operaciones con muchos reintentos.
     */
    private const SOCKET_TIMEOUT = 15;

    private const TIMEOUT_STEP = 10;

    private const TIMEOUT_MAX = 60;

    public function __construct(protected Device $device) {}

    /**
     * @param  int|null  $timeoutOverride  Timeout de socket a usar en vez de
     *                                     SOCKET_TIMEOUT. Lo usa withRetries
     *                                     para el timeout adaptativo.
     */
    public function client(): ZKTeco
    {
        return $this->clientWithTimeout(self::SOCKET_TIMEOUT);
    }

    protected function clientWithTimeout(int $timeout): ZKTeco
    {
        if (! defined('ZK_LIB_LOG')) {
            define('ZK_LIB_LOG', storage_path('logs/zkteco-error.log'));
        }

        $zk = new ZKTeco(
            $this->device->ip,
            (int) $this->device->port,
            true,
            $timeoutOverride ?? self::SOCKET_TIMEOUT,
            (string) ($this->device->password ?? '')
        );

        $zk->setPing(true, true);

        return $zk;
    }

    /**
     * Ejecuta $callback con reintentos, backoff progresivo y timeout
     * adaptativo. $callback recibe el timeout (segundos) que debería usar
     * en ESE intento; si la operación crea su propio cliente ZKTeco
     * internamente (p.ej. boot()), debe pasarle ese timeout a client().
     * Solo debe envolver operaciones idempotentes de red (ping, connect de
     * lectura); nunca escrituras que ya mutaron estado en el hardware, para
     * no duplicar efectos si el reintento sí llega a ejecutarse dos veces.
     */
    private function withRetries(callable $callback, string $context, int $maxRetries = self::MAX_RETRIES): mixed
    {
        $attempt = 0;
        $lastException = null;
        $timeout = self::SOCKET_TIMEOUT;

        while ($attempt < $maxRetries) {
            $attempt++;

            try {
                return $callback($timeout);
            } catch (Throwable $e) {
                $lastException = $e;

                Log::warning("ZKTeco {$context}: intento {$attempt}/{$maxRetries} (timeout {$timeout}s) falló: ".$e->getMessage(), [
                    'device' => $this->device->ip,
                ]);

                if ($attempt < $maxRetries) {
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
                    $timeout = min(self::TIMEOUT_MAX, $timeout + self::TIMEOUT_STEP);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Punto único por el que pasa toda operación contra el checador. Aprovecha
     * el intento de conexión para mantener devices.status fiel a la realidad
     * (docs/zkteco.md §2): éxito → online; fallo de red → offline. Así el
     * indicador del dashboard se alimenta de la actividad real, sin depender
     * del botón manual de verificación.
     */
    protected function boot(): ZKTeco
    {
        try {
            // El cliente se recrea en cada intento (no se reutiliza el
            // mismo $zk) para que el timeout adaptativo de withRetries
            // realmente tome efecto: un socket UDP ya creado con timeout
            // corto no cambia su timeout a medio camino.
            $zk = $this->withRetries(function (int $timeout) {
                $client = $timeout === self::SOCKET_TIMEOUT
                    ? $this->client()
                    : $this->clientWithTimeout($timeout);

                if (! $client->ping()) {
                    throw new ZktecoConnectionException(
                        'No se pudo conectar al checador en '.$this->device->ip.':'.$this->device->port,
                        $this->device->ip
                    );
                }

                return $client;
            }, 'ping');

            $this->markDeviceStatus('online');
        } catch (ZktecoConnectionException $exception) {
            $this->markDeviceStatus('offline');

            throw $exception;
        } catch (Throwable $exception) {
            $this->markDeviceStatus('offline');

            throw new ZktecoConnectionException(
                'El checador no respondió en '.$this->device->ip.':'.$this->device->port,
                $this->device->ip,
                $exception
            );
        }

        return $zk;
    }

    /**
     * Persistencia tolerante a fallos: si la BD falla al guardar el estatus,
     * la operación sobre el dispositivo debe continuar igualmente.
     */
    private function markDeviceStatus(string $status): void
    {
        try {
            $this->device->update(['status' => $status]);
        } catch (Throwable $e) {
            Log::warning('No se pudo actualizar el estado del checador '.$this->device->id.': '.$e->getMessage());
        }
    }

    public function connect(): bool
    {
        $zk = $this->boot();

        if (! $zk) {
            return false;
        }

        try {
            $connected = $this->withRetries(fn () => $zk->connect(), 'connect');
            $zk->disconnect();

            return $connected;
        } catch (Throwable $e) {
            Log::error('ZKTeco connect error: '.$e->getMessage(), ['device' => $this->device->ip]);

            return false;
        }
    }

    public function deviceStatus(): string
    {
        try {
            $status = $this->connect() ? 'online' : 'offline';
        } catch (Throwable $e) {
            $status = 'offline';
        }

        $this->device->update(['status' => $status]);

        return $status;
    }

    public function info(): array
    {
        try {
            $zk = $this->boot();
        } catch (ZktecoConnectionException $e) {
            return [];
        }

        try {
            $this->withRetries(function () use ($zk) {
                if (! $zk->connect()) {
                    throw new ZktecoConnectionException('No se pudo abrir la sesión con el checador.', $this->device->ip);
                }

                return true;
            }, 'info:connect');

            $info = [
                'device_name' => $zk->deviceName(),
                'device_id' => $zk->deviceId(),
                'serial' => $zk->serialNumber(),
                'vendor' => $zk->vendorName(),
                'version' => $zk->version(),
                'os' => $zk->osVersion(),
                'platform' => $zk->platform(),
                'fm_version' => $zk->fmVersion(),
                'pin_width' => $zk->pinWidth(),
                'time' => $zk->getTime(),
            ];

            $zk->disconnect();
        } catch (Throwable $e) {
            Log::error('ZKTeco info error: '.$e->getMessage(), ['device' => $this->device->ip]);
            $info = [];
        }

        return $info;
    }

    public function getUsers(): array
    {
        $zk = $this->boot();

        try {
            $this->withRetries(function () use ($zk) {
                if (! $zk->connect()) {
                    throw new ZktecoConnectionException('No se pudo abrir la sesión para descargar usuarios.', $this->device->ip);
                }

                return true;
            }, 'getUsers:connect', self::MAX_RETRIES_LONG);
            $users = $zk->getUsers();
            $zk->disconnect();

            return $users;
        } catch (ZktecoConnectionException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('ZKTeco getUsers error: '.$e->getMessage(), ['device' => $this->device->ip]);

            return [];
        }
    }

    public function getAttendances(): array
    {
        $zk = $this->boot();

        try {
            $this->withRetries(function () use ($zk) {
                if (! $zk->connect()) {
                    throw new ZktecoConnectionException('No se pudo abrir la sesión para descargar asistencias.', $this->device->ip);
                }

                return true;
            }, 'getAttendances:connect', self::MAX_RETRIES_LONG);
            $records = $zk->getAttendances();
            $zk->disconnect();

            return $records;
        } catch (ZktecoConnectionException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('ZKTeco getAttendances error: '.$e->getMessage(), ['device' => $this->device->ip]);

            return [];
        }
    }

    /**
     * Obtiene usuarios y asistencias en una sola conexión TCP.
     * Útil para sincronización inicial completa.
     */
    public function getUsersAndAttendances(): array
    {
        $zk = $this->boot();

        try {
            $this->withRetries(function () use ($zk) {
                if (! $zk->connect()) {
                    throw new ZktecoConnectionException('No se pudo abrir la sesión con el checador.', $this->device->ip);
                }

                return true;
            }, 'getUsersAndAttendances:connect', self::MAX_RETRIES_LONG);

            $users = $zk->getUsers();
            $attendances = $zk->getAttendances();

            $zk->disconnect();

            return [
                'users' => $users,
                'attendances' => $attendances,
            ];
        } catch (ZktecoConnectionException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('ZKTeco getUsersAndAttendances error: '.$e->getMessage(), ['device' => $this->device->ip]);

            return ['users' => [], 'attendances' => []];
        }
    }

    /**
     * Descarga los usuarios del checador y los concilia con el catálogo
     * central:
     * - Match por user_id (PIN/badge), la clave unificadora global.
     * - Si no existe en el catálogo, firstOrCreate.
     * - Vinculación por pivote con syncWithoutDetaching-like: attach si es
     *   nuevo enrolamiento, updateExistingPivot si ya estaba (touch al padre).
     * La metadata de hardware (device_uid/role/card_number/password) vive en
     * device_employee, nunca en employees.
     */
    public function syncUsers(?callable $onProgress = null): array
    {
        $created = 0;
        $updated = 0;
        $users = $this->getUsers();
        $total = count($users);

        // Enrolamientos actuales de ESTE dispositivo indexados por PIN del
        // catálogo: evita consultar la pivote por cada usuario descargado.
        $enrolledByUserId = $this->device->employees()
            ->get(['employees.id', 'employees.user_id'])
            ->keyBy('user_id');

        foreach ($users as $index => $user) {
            $userId = (string) $user['user_id'];
            $name = (string) $user['name'];

            $employee = Employee::firstOrCreate(
                ['user_id' => $userId],
                ['name' => $name]
            );

            // Último dispositivo sincronizado gana: mantiene el nombre del
            // catálogo alineado con el checador más reciente (paridad con el
            // comportamiento previo a la refactorización).
            if (! $employee->wasRecentlyCreated && $employee->name !== $name) {
                $employee->update(['name' => $name]);
            }

            $pivotAttributes = [
                'device_uid' => (int) $user['uid'],
                'role' => (int) $user['role'],
                'card_number' => $user['card_no'] ?? null,
                'password' => $user['password'] ?? null,
                'active' => true,
            ];

            // Tarjeta repetida entre personas distintas del mismo equipo:
            // se anula aquí para no violar el único [device_id, card_number].
            if (! empty($pivotAttributes['card_number'])
                && DB::table('device_employee')
                    ->where('device_id', $this->device->id)
                    ->where('card_number', $pivotAttributes['card_number'])
                    ->where('employee_id', '!=', $employee->id)
                    ->exists()) {
                $pivotAttributes['card_number'] = null;
            }

            if ($enrolledByUserId->has($userId)) {
                $employee->devices()->updateExistingPivot($this->device->id, $pivotAttributes);
                $updated++;
            } else {
                $employee->devices()->attach($this->device->id, $pivotAttributes);
                $created++;
            }

            if ($onProgress) {
                $onProgress($index + 1, $total);
            }
        }

        return ['created' => $created, 'updated' => $updated, 'total' => $total];
    }

    public function syncAttendances(?callable $onProgress = null): array
    {
        $created = 0;
        $records = $this->getAttendances();

        // Resolución PIN → catálogo a través de la pivote: solo cuentan los
        // empleados enrolados en ESTE checador.
        $employeeIds = $this->device->employees()->pluck('employees.id', 'employees.user_id');

        $total = count($records);
        foreach ($records as $index => $record) {
            $userKey = (string) $record['user_id'];
            $employeeId = $employeeIds[$userKey] ?? null;
            $type = (int) ($record['type'] ?? 0);

            $identity = [
                'device_id' => $this->device->id,
                'user_id' => $userKey,
                'state' => (int) $record['state'],
                'recorded_at' => $record['record_time'],
            ];
            $inserted = DB::table('attendances')->insertOrIgnore(array_merge($identity, [
                'employee_id' => $employeeId,
                'type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $attendance = Attendance::query()->where($identity)->firstOrFail();
            $created += $inserted;

            // Siempre actualizamos employee_id y type para asegurar consistencia
            // (ej. si el empleado cambió de dispositivo)
            if ($attendance->employee_id !== $employeeId) {
                $attendance->employee_id = $employeeId;
                $attendance->save();
            }
            if ($attendance->type !== $type) {
                $attendance->type = $type;
                $attendance->save();
            }

            if ($onProgress) {
                $onProgress($index + 1, $total);
            }
        }

        return ['created' => $created, 'total' => $total];
    }

    public function syncFingerprints(?int $employeeId = null, int $batchSize = 25, ?callable $onProgress = null): array
    {
        $created = 0;
        $updated = 0;
        $processed = 0;
        $zk = $this->boot();

        if (! $zk) {
            return ['created' => 0, 'updated' => 0, 'processed' => 0];
        }

        try {
            $this->withRetries(function () use ($zk) {
                if (! $zk->connect()) {
                    throw new ZktecoConnectionException('No se pudo abrir la sesión para descargar huellas.', $this->device->ip);
                }

                return true;
            }, 'syncFingerprints:connect', self::MAX_RETRIES_LONG);

            $employeesQuery = $this->device->employees();
            if ($employeeId) {
                $employeesQuery->whereKey($employeeId);
            }

            $totalEmployees = (clone $employeesQuery)->count();
            Log::info('Sync fingerprints starting', [
                'device' => $this->device->ip,
                'employeeId' => $employeeId,
                'totalEmployees' => $totalEmployees,
            ]);
            $employeesQuery->chunkById($batchSize, function ($employees) use ($zk, &$created, &$updated, &$processed, $onProgress, $totalEmployees): void {
                foreach ($employees as $employee) {
                    try {
                        $result = $this->syncFingerprintForEmployee($zk, $employee);
                        $created += $result['created'];
                        $updated += $result['updated'];
                        Log::info('Fingerprint processed for employee', [
                            'employeeId' => $employee->id,
                            'uid' => $employee->pivot->device_uid,
                            'created' => $result['created'],
                            'updated' => $result['updated'],
                        ]);
                    } catch (Throwable $e) {
                        Log::error('ZKTeco huella fallida para empleado '.$employee->id.': '.$e->getMessage(), ['device' => $this->device->ip]);
                    }
                    $processed++;
                }
                if ($onProgress) {
                    $onProgress($processed, $totalEmployees);
                }
            });

            $zk->disconnect();
        } catch (ZktecoConnectionException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('ZKTeco sync fingerprints error: '.$e->getMessage(), ['device' => $this->device->ip]);
            throw $e;
        }

        Log::info('Sync fingerprints completed', [
            'device' => $this->device->ip,
            'created' => $created,
            'updated' => $updated,
            'processed' => $processed,
        ]);

        return compact('created', 'updated', 'processed');
    }

    /**
     * Extrae las plantillas de UN empleado desde el checador conectado.
     * Cada huella queda atribuida al dispositivo de origen
     * (único [device_id, employee_id, finger]) y se refresca el contador en
     * caché del enrolamiento (device_employee.fingerprint_count).
     */
    protected function syncFingerprintForEmployee(ZKTeco $zk, Employee $employee): array
    {
        $created = 0;
        $updated = 0;
        // Reintento por empleado: updateOrCreate más abajo es idempotente,
        // así que reintentar la extracción ante un corte momentáneo no
        // duplica nada y evita perder el resto del lote por un solo blip.
        $fingerprints = $this->withRetries(
            fn () => $zk->getFingerprint((int) $employee->pivot->device_uid),
            'getFingerprint:employee-'.$employee->id,
            self::MAX_RETRIES_LONG
        );

        Log::info('ZKTeco huellas recibidas', [
            'device' => $this->device->ip,
            'employee' => $employee->id,
            'uid' => $employee->pivot->device_uid,
            'count' => count($fingerprints),
        ]);

        $syncedFingers = array_map('intval', array_keys($fingerprints));

        foreach ($fingerprints as $finger => $template) {
            $finger = (int) $finger;
            $fingerprint = Fingerprint::updateOrCreate(
                [
                    'device_id' => $this->device->id,
                    'employee_id' => $employee->id,
                    'finger' => $finger,
                ],
                [
                    'template' => (string) $template,
                    'template_hash' => hash('sha256', (string) $template),
                ]
            );

            $fingerprint->wasRecentlyCreated ? $created++ : $updated++;
        }

        // Re-atribución de huellas legadas: los dedos recién extraídos desde
        // este dispositivo ya tienen su fila con origen, así que la copia
        // legada (device_id NULL) del mismo dedo es un duplicado y se elimina.
        // Los dedos que el equipo ya NO reporta conservan su fila NULL como
        // única copia existente (y sirven de respaldo en uploadFingerprints).
        if ($syncedFingers !== []) {
            Fingerprint::query()
                ->where('employee_id', $employee->id)
                ->whereNull('device_id')
                ->whereIn('finger', $syncedFingers)
                ->delete();
        }

        $employee->devices()->updateExistingPivot($this->device->id, [
            'fingerprint_count' => Fingerprint::query()
                ->where('device_id', $this->device->id)
                ->where('employee_id', $employee->id)
                ->count(),
        ]);

        Log::info('ZKTeco huellas guardadas', [
            'device' => $this->device->ip,
            'employee' => $employee->id,
            'created' => $created,
            'updated' => $updated,
        ]);

        return compact('created', 'updated');
    }

    public function uploadFingerprints(Employee $employee): bool
    {
        // Membresía vía pivote: el empleado debe estar enrolado en ESTE equipo.
        // El enrolamiento trae el device_uid local que el hardware espera.
        $enrollment = $this->device->employees()
            ->whereKey($employee->getKey())
            ->first();

        if (! $enrollment) {
            return false;
        }

        try {
            if (! $zk = $this->boot()) {
                return false;
            }
            if (! $this->withRetries(fn () => $zk->connect(), 'uploadFingerprints:connect')) {
                return false;
            }

            // Plantillas propias de este dispositivo; las legadas sin origen
            // (device_id NULL) también sirven hasta la primera re-extracción.
            $templates = $employee->fingerprints()
                ->get(['finger', 'template', 'device_id'])
                ->sortBy(fn (Fingerprint $fingerprint): int => $fingerprint->device_id === $this->device->id
                    ? 0
                    : ($fingerprint->device_id === null ? 1 : 2))
                ->unique('finger')
                ->mapWithKeys(fn (Fingerprint $fingerprint): array => [
                    (int) $fingerprint->finger => (string) $fingerprint->template,
                ])
                ->all();
            if (! $templates) {
                $zk->disconnect();

                return false;
            }

            $templatesForDevice = [];
            foreach ($templates as $finger => $template) {
                $normalizedTemplate = $this->forDevice(
                    (string) $template,
                    (int) $enrollment->pivot->device_uid,
                    (int) $finger
                );
                if ($normalizedTemplate === null) {
                    $zk->disconnect();

                    return false;
                }
                $templatesForDevice[(int) $finger] = $normalizedTemplate;
            }

            $result = $zk->setFingerprint((int) $enrollment->pivot->device_uid, $templatesForDevice);
            $zk->disconnect();

            return (int) $result === count($templates);
        } catch (Throwable $e) {
            Log::error('ZKTeco upload fingerprints error: '.$e->getMessage(), ['device' => $this->device->ip, 'employee' => $employee->id]);

            return false;
        }
    }

    public function uploadFingerprint(Employee $employee, Fingerprint $fingerprint): bool
    {
        if ($fingerprint->employee_id !== $employee->id) {
            return false;
        }

        $enrollment = $this->device->employees()
            ->whereKey($employee->getKey())
            ->first();
        if (! $enrollment) {
            return false;
        }

        $zk = $this->boot();
        if (! $zk) {
            return false;
        }

        try {
            if (! $this->withRetries(fn () => $zk->connect(), 'uploadFingerprint:connect')) {
                return false;
            }

            $templateData = (string) $fingerprint->template;
            if ($fingerprint->device_id && (int) $fingerprint->device_id !== (int) $this->device->id) {
                $sourceTemplate = $this->readFingerprintFromSource($employee, $fingerprint);
                if ($sourceTemplate !== null) {
                    $templateData = $sourceTemplate;
                }
            }

            $template = $this->forDevice($templateData, (int) $enrollment->pivot->device_uid, (int) $fingerprint->finger);
            if ($template === null) {
                $zk->disconnect();

                return false;
            }

            $result = $zk->setFingerprint(
                (int) $enrollment->pivot->device_uid,
                [(int) $fingerprint->finger => $template]
            );
            $zk->disconnect();

            return (int) $result === 1;
        } catch (Throwable $e) {
            Log::error('ZKTeco upload fingerprint error: '.$e->getMessage(), [
                'device' => $this->device->ip,
                'employee' => $employee->id,
                'finger' => $fingerprint->finger,
            ]);

            return false;
        }
    }

    private function forDevice(string $template, int $uid, int $finger): ?string
    {
        // getFingerprint() includes a six-byte header with the source UID and
        // finger. The target device rejects a template whose header points to
        // another UID, so rewrite only that header and preserve the binary body.
        if (strlen($template) < 6 || $uid < 1 || $uid > 65535 || $finger < 0 || $finger > 9) {
            return null;
        }

        $template = substr_replace($template, chr($uid % 256).chr($uid >> 8), 2, 2);
        $template = substr_replace($template, chr($finger), 4, 1);

        return $template;
    }

    private function readFingerprintFromSource(Employee $employee, Fingerprint $fingerprint): ?string
    {
        $sourceDevice = Device::find($fingerprint->device_id);
        if (! $sourceDevice) {
            return null;
        }

        $sourceEnrollment = $sourceDevice->employees()->whereKey($employee->getKey())->first();
        if (! $sourceEnrollment) {
            return null;
        }

        $sourceClient = (new self($sourceDevice))->boot();
        try {
            if (! $this->withRetries(fn () => $sourceClient->connect(), 'readFingerprintSource:connect')) {
                return null;
            }

            $templates = $sourceClient->getFingerprint((int) $sourceEnrollment->pivot->device_uid);
            $sourceClient->disconnect();

            return isset($templates[(int) $fingerprint->finger])
                ? (string) $templates[(int) $fingerprint->finger]
                : null;
        } catch (Throwable $e) {
            Log::warning('No se pudo leer la huella desde el dispositivo origen.', [
                'source_device' => $sourceDevice->ip,
                'employee' => $employee->id,
                'finger' => $fingerprint->finger,
            ]);

            return null;
        }
    }

    public function removeFingerprint(Employee $employee, Fingerprint $fingerprint): bool
    {
        if ($fingerprint->employee_id !== $employee->id || $fingerprint->device_id !== $this->device->id) {
            return false;
        }

        $enrollment = $this->device->employees()
            ->whereKey($employee->getKey())
            ->first();
        if (! $enrollment) {
            return false;
        }

        $zk = $this->boot();
        if (! $zk) {
            return false;
        }

        try {
            if (! $this->withRetries(fn () => $zk->connect(), 'removeFingerprint:connect')) {
                return false;
            }

            $removed = $zk->removeFingerprint(
                (int) $enrollment->pivot->device_uid,
                [(int) $fingerprint->finger]
            );
            $zk->disconnect();

            return (int) $removed === 1;
        } catch (Throwable $e) {
            Log::error('ZKTeco remove fingerprint error: '.$e->getMessage(), [
                'device' => $this->device->ip,
                'employee' => $employee->id,
                'finger' => $fingerprint->finger,
            ]);

            return false;
        }
    }

    public function setUser(array $data): bool
    {
        $devicePassword = (string) ($this->device->password ?? '');

        $data = array_merge([
            'uid' => null,
            'user_id' => null,
            'name' => null,
            'password' => $devicePassword ?: '1234',
            'role' => 0,
            'card_number' => null,
        ], $data);

        $zk = $this->boot();

        if (! $zk) {
            return false;
        }

        try {
            if (! $this->withRetries(fn () => $zk->connect(), 'setUser:connect')) {
                return false;
            }

            $uid = $data['uid'] ?: $this->nextUid($zk);
            if (! $uid) {
                $zk->disconnect();

                return false;
            }

            $result = $zk->setUser(
                (int) $uid,
                (string) $data['user_id'],
                (string) $data['name'],
                (string) $data['password'],
                (int) $data['role'],
                (int) ($data['card_number'] ?: 0)
            );
            $zk->disconnect();

            if ($result) {
                $this->syncUsers();
            }

            return (bool) $result;
        } catch (InvalidParamException|Throwable $e) {
            Log::error('ZKTeco setUser error: '.$e->getMessage(), ['device' => $this->device->ip]);

            return false;
        }
    }

    public function enrollEmployee(Employee $employee, ?string $password = null, ?string $cardNumber = null, int $role = 0): bool
    {
        $enrollment = $this->device->employees()->whereKey($employee->getKey())->first();

        if (! $this->setUser([
            'uid' => $enrollment?->pivot->device_uid,
            'user_id' => $employee->user_id,
            'name' => $employee->name,
            'password' => $password ?: '1234',
            'role' => $role,
            'card_number' => $cardNumber,
        ])) {
            return false;
        }

        $hasFingerprints = $employee->fingerprints()->exists();

        return ! $hasFingerprints || $this->uploadFingerprints($employee);
    }

    protected function nextUid(ZKTeco $zk): ?int
    {
        // El espacio de UIDs es por checador: se calcula desde la pivote de
        // ESTE dispositivo, no del catálogo global.
        $maxUid = (int) DB::table('device_employee')
            ->where('device_id', $this->device->id)
            ->max('device_uid');

        if ($maxUid <= 0 || $maxUid >= 65534) {
            foreach ($zk->getUsers() as $user) {
                $maxUid = max($maxUid, (int) $user['uid']);
            }
        }

        return $maxUid >= 65535 ? null : $maxUid + 1;
    }

    /**
     * Elimina al usuario del hardware y desvincula su enrolamiento en este
     * dispositivo. El registro del catálogo central solo se elimina cuando
     * queda sin ningún enrolamiento: la misma persona puede vivir en otros
     * checadores (sus asistencias históricas se preservan vía nullOnDelete).
     */
    public function removeUser(int $uid): bool
    {
        $zk = $this->boot();

        if (! $zk) {
            return false;
        }

        try {
            if (! $this->withRetries(fn () => $zk->connect(), 'removeUser:connect')) {
                return false;
            }
            $result = $zk->removeUser($uid);
            $zk->disconnect();

            if ($result) {
                $pivot = DeviceEmployee::query()
                    ->where('device_id', $this->device->id)
                    ->where('device_uid', $uid)
                    ->first();

                if ($pivot) {
                    $employee = $pivot->employee;

                    $employee->devices()->detach($this->device->id);

                    if (! $employee->devices()->exists()) {
                        $employee->delete();
                    }
                }
            }

            return (bool) $result;
        } catch (Throwable $e) {
            Log::error('ZKTeco removeUser error: '.$e->getMessage(), ['device' => $this->device->ip]);

            return false;
        }
    }

    public function setTime(string $datetime): bool
    {
        $zk = $this->boot();

        if (! $zk) {
            return false;
        }

        try {
            if (! $this->withRetries(fn () => $zk->connect(), 'setTime:connect')) {
                return false;
            }
            $result = $zk->setTime($datetime);
            $zk->disconnect();

            return (bool) $result;
        } catch (Throwable $e) {
            Log::error('ZKTeco setTime error: '.$e->getMessage(), ['device' => $this->device->ip]);

            return false;
        }
    }

    public function clearAttendance(): bool
    {
        $zk = $this->boot();

        if (! $zk) {
            return false;
        }

        try {
            if (! $this->withRetries(fn () => $zk->connect(), 'clearAttendance:connect')) {
                return false;
            }
            $result = $zk->clearAttendance();
            $zk->disconnect();

            return (bool) $result;
        } catch (Throwable $e) {
            Log::error('ZKTeco clearAttendance error: '.$e->getMessage(), ['device' => $this->device->ip]);

            return false;
        }
    }

    public function restoreDevice(): bool
    {
        $zk = $this->boot();

        if (! $zk) {
            return false;
        }

        try {
            if (! $this->withRetries(fn () => $zk->connect(), 'restoreDevice:connect')) {
                return false;
            }
            $result = $zk->enableDevice();
            $zk->disconnect();

            return (bool) $result;
        } catch (Throwable $e) {
            Log::error('ZKTeco restoreDevice error: '.$e->getMessage(), ['device' => $this->device->ip]);

            return false;
        }
    }
}