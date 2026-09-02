<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivote empleado ↔ checador. Toda la metadata de hardware vive aquí:
     * device_uid (ID numérico interno del equipo), role, card_number, password
     * (PIN del dispositivo) y active son atributos del enrolamiento, no del
     * catálogo central.
     *
     * Índices:
     * - unique [device_id, device_uid]: un UID físico no puede repetirse en el
     *   mismo checador.
     * - unique [device_id, card_number]: evita que dos empleados distintos
     *   compartan la misma tarjeta EN EL MISMO equipo (error de captura).
     *   No es único global a propósito: la misma persona enrolada en varios
     *   dispositivos produce una fila pivote por equipo con su misma tarjeta.
     * - index employee_id: resolución inversa empleado → dispositivos.
     */
    public function up(): void
    {
        Schema::create('device_employee', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('device_uid');
            $table->unsignedTinyInteger('role')->default(0);
            $table->string('card_number')->nullable();
            $table->string('password')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('fingerprint_count')->default(0);
            $table->timestamps();

            $table->unique(['device_id', 'device_uid'], 'device_employee_device_id_device_uid_unique');
            $table->unique(['device_id', 'card_number'], 'device_employee_device_id_card_number_unique');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_employee');
    }
};
