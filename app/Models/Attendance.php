<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'employee_id',
        'user_id',
        'state',
        'type',
        'recorded_at',
    ];

    protected $casts = [
        'state' => 'integer',
        'type' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public static function uniqueAttendances()
    {
        return self::query()
            ->whereIn('id', self::query()
                ->selectRaw('MAX(id)')
                ->groupBy('employee_id', 'recorded_at'))
            ->latest('recorded_at');
    }

    /**
     * Modo de checado efectivo del registro. En este firmware ZKTeco el
     * byte "type" del log (ver vendor coding-libs/zkteco-php,
     * Services/Attendance.php) es el modo elegido en el terminal
     * (0=Entrada, 1=Salida, 2/3=descanso, 4/5=T.E.) mientras "state" viene
     * constante en 1; en firmwares que reportan al revés, "state" respalda.
     */
    private function punchStatus(): int
    {
        if (isset(self::states()[$this->type])) {
            return $this->type;
        }

        return isset(self::states()[$this->state]) ? $this->state : $this->type;
    }

    public function stateLabel(): string
    {
        return self::states()[$this->punchStatus()] ?? 'Desconocido';
    }

    /**
     * Etiqueta corta para badges de UI ("Entrada T.E." vs "Entrada de tiempo extra").
     */
    public function shortStateLabel(): string
    {
        return match ($this->punchStatus()) {
            0 => 'Entrada',
            1 => 'Salida',
            4 => 'Entrada T.E.',
            5 => 'Salida T.E.',
            default => $this->stateLabel(),
        };
    }

    /**
     * Clase de color del sistema de tokens (app.css .cat-*) para el badge de estado.
     */
    public function stateColorClass(): string
    {
        return match ($this->punchStatus()) {
            0 => 'cat-green',
            1 => 'cat-blue',
            4 => 'cat-purple',
            5 => 'cat-lavender',
            default => 'cat-gray',
        };
    }

    /**
     * Nota: en firmwares como el de este proyecto, el byte "type" del log es
     * el modo de checado (ver punchStatus()), NO el método de verificación;
     * esta etiqueta solo es fiel en equipos donde type reporta verificación.
     */
    public function verificationTypeLabel(): string
    {
        return self::verificationTypes()[$this->type] ?? 'Método '.$this->type;
    }

    public static function states(): array
    {
        return [
            0 => 'Entrada',
            1 => 'Salida',
            2 => 'Salida de descanso',
            3 => 'Regreso de descanso',
            4 => 'Entrada de tiempo extra',
            5 => 'Salida de tiempo extra',
            255 => 'Desconocido',
        ];
    }

    public static function verificationTypes(): array
    {
        return [
            0 => 'Huella',
            1 => 'Contraseña',
            2 => 'Código',
            3 => 'Tarjeta',
            4 => 'Huella',
            5 => 'Rostro',
        ];
    }
}
