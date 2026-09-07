<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Academia\Profesor;
use App\Models\Academia\HorarioDet;
use App\Models\Academia\Ciclo;
use App\Models\Academia\Contrato;
use App\Models\Academia\Sede;
use App\Models\Employee;
use Illuminate\Support\Collection;

class PersonaContratosResolver
{
    /**
     * Unifica los contratos de una persona (admin + PTC + PA)
     * Equivalente a get_persona_contratos() de ProyectoBase
     */
    public function resolver(string $claveProfesor, Ciclo $ciclo): array
    {
        $profesor = Profesor::where('clave_profesor', $claveProfesor)->first();
        if (! $profesor) {
            return [];
        }

        $contratos = [];

        // 1. Contrato administrativo (Employee)
        $admin = $this->getContratoAdmin($claveProfesor);
        if ($admin) {
            $contratos[] = $admin;
        }

        // 2. Clases del ciclo (PTC y PA)
        $clasesPorOrigen = $this->getClasesPorOrigen($claveProfesor, $ciclo);

        foreach ($clasesPorOrigen as $origen => $lista) {
            $tipo = $origen === 'HD' ? 'PTC' : 'PA';
            $contratos[] = [
                'tipo' => $tipo,
                'origen_horario' => $origen,
                'jornada_fija' => null,
                'clases_ciclo' => $lista,
            ];
        }

        return $contratos;
    }

    /**
     * Contrato administrativo desde Employee
     */
    protected function getContratoAdmin(string $claveProfesor): ?array
    {
        $employee = Employee::where('clave_profesor', $claveProfesor)->first();
        if (! $employee) {
            return null;
        }

        $contrato = $employee->contratoRel ?? null;

        return [
            'tipo' => $employee->type === 'admin' ? 'ADMIN' : 'EMPLEADO',
            'origen_horario' => 'ADMIN',
            'jornada_fija' => $this->extraerJornada($employee),
            'clases_ciclo' => [],
        ];
    }

    /**
     * Extrae jornada fija del empleado administrativo
     */
    protected function extraerJornada(Employee $employee): ?array
    {
        // Si hay horarios asignados en device_employee o tabla similar
        // Por ahora retornamos null - se puede extender con tabla horarios_laborales
        return null;
    }

    /**
     * Agrupa clases por origen_horario (HD = PTC, CA = PA)
     */
    protected function getClasesPorOrigen(string $claveProfesor, Ciclo $ciclo): array
    {
        $horarios = HorarioDet::with(['materia', 'grupo', 'sede', 'sesionBase'])
            ->where('clave_profesor', $claveProfesor)
            ->where('inicial', $ciclo->inicial)
            ->where('final', $ciclo->final)
            ->where('periodo', $ciclo->periodo)
            ->where('activo', true)
            ->orderBy('dia')
            ->orderBy('sesion')
            ->get();

        $porOrigen = [];
        foreach ($horarios as $h) {
            $origen = $h->origen_horario ?? 'CA';
            if (! isset($porOrigen[$origen])) {
                $porOrigen[$origen] = [];
            }

            $inicio = $h->sesionBase?->hora_inicio?->format('H:i') ?? '??:??';
            $fin = $h->sesionBase?->hora_fin?->format('H:i') ?? '??:??';

            $porOrigen[$origen][] = [
                'dia' => $h->dia,
                'inicio' => $inicio,
                'fin' => $fin,
                'materia' => $h->materia?->nombre_asignatura ?? $h->clave_asignatura,
                'grupo' => $h->codigo_grupo,
                'aula' => $h->ubicacion,
                'tipo' => $h->origen_horario === 'HD' ? 'PTC' : 'PA',
                'horas_tp' => $h->horas_teoria_practica,
            ];
        }

        return $porOrigen;
    }

    /**
     * Obtiene todos los contratos de un profesor (para vista completa)
     */
    public function getContratosCompletos(string $claveProfesor, ?Ciclo $ciclo = null): array
    {
        $profesor = Profesor::where('clave_profesor', $claveProfesor)->first();
        if (! $profesor) {
            return [];
        }

        $ciclo = $ciclo ?? $this->getCicloActual();

        return [
            'profesor' => [
                'clave' => $profesor->clave_profesor,
                'nombre' => $profesor->nombre_completo,
                'departamento' => $profesor->departamento,
                'contrato' => $profesor->contratoRel?->descripcion ?? $profesor->contrato,
                'origen_horario' => $profesor->origen_horario_label,
                'status' => $profesor->status_label,
                'sede' => $profesor->sede?->descripcion,
            ],
            'contratos' => $this->resolver($claveProfesor, $ciclo),
        ];
    }

    protected function getCicloActual(): ?Ciclo
    {
        return \App\Models\Academia\Ciclo::whereHas('horarios')
            ->latest('inicial')
            ->latest('final')
            ->latest('periodo')
            ->first();
    }

    /**
     * Obtiene contratos administrativos de un empleado (para Employee)
     */
    public function getContratosEmpleado(Employee $employee): array
    {
        $contratos = [];

        if ($employee->type === 'admin' || $employee->type === 'teacher') {
            $contrato = $employee->contratoRel;
            $contratos[] = [
                'tipo' => 'ADMIN',
                'origen' => 'EMPLEADO',
                'contrato' => $contrato?->descripcion ?? $employee->contrato,
                'jornada' => null, // Se puede extender con tabla horarios_laborales
                'sede' => $employee->sede?->descripcion ?? $employee->id_campus,
            ];
        }

        if ($employee->type === 'teacher' && $employee->clave_profesor) {
            $ciclo = $this->getCicloActual();
            if ($ciclo) {
                $profesorContratos = $this->resolver($employee->clave_profesor, $ciclo);
                $contratos = array_merge($contratos, $profesorContratos);
            }
        }

        return $contratos;
    }
}