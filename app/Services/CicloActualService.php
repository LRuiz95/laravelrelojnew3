<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NoCiclosConfiguradosException;
use App\Models\Academia\Ciclo;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class CicloActualService
{
    public const SESSION_KEY = 'ciclo_actual';

    /**
     * Resuelve el ciclo actual siguiendo prioridad:
     * 1. Parámetro URL ?ciclo_principal=
     * 2. Sesión guardada
     * 3. Último ciclo activo
     */
    public function resolve(Request $request): Ciclo
    {
        // 1. Parámetro URL
        if ($request->filled('ciclo_principal')) {
            $ciclo = $this->findByLabel($request->get('ciclo_principal'));
            if ($ciclo) {
                $this->storeInSession($ciclo);
                return $ciclo;
            }
        }

        // 2. Sesión
        if (Session::has(self::SESSION_KEY)) {
            $ciclo = $this->findByLabel(Session::get(self::SESSION_KEY));
            if ($ciclo) {
                return $ciclo;
            }
        }

        // 3. Default: último ciclo con datos
        return $this->getDefaultCiclo();
    }

    public function findByLabel(string $label): ?Ciclo
    {
        $parts = explode('-', $label);
        if (count($parts) !== 3) {
            return null;
        }

        return Ciclo::where('inicial', (int)$parts[0])
            ->where('final', (int)$parts[1])
            ->where('periodo', (int)$parts[2])
            ->first();
    }

    public function getDefaultCiclo(): Ciclo
    {
        $ciclo = Ciclo::query()
            ->activo()
            ->latest('inicial')
            ->latest('final')
            ->latest('periodo')
            ->first();

        if (! $ciclo) {
            throw new NoCiclosConfiguradosException();
        }

        return $ciclo;
    }

    /**
     * Obtiene el ciclo actual para el header/global.
     * Prioridad: parámetro request > sesión > default activo.
     * Igual que resolve(), para mantener consistencia.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Academia\Ciclo|null
     */
    public function current(?Request $request = null): ?Ciclo
    {
        // 1. Parámetro request (máxima prioridad)
        if ($request && $request->filled('ciclo_principal')) {
            $ciclo = $this->findByLabel($request->get('ciclo_principal'));
            if ($ciclo) {
                return $ciclo;
            }
        }

        // 2. Sesión
        if (Session::has(self::SESSION_KEY)) {
            $ciclo = $this->findByLabel(Session::get(self::SESSION_KEY));
            if ($ciclo) {
                return $ciclo;
            }
        }

        // 3. Default: ciclo activo más reciente
        return $this->getDefaultCiclo();
    }

    public function storeInSession(Ciclo $ciclo): void
    {
        Session::put(self::SESSION_KEY, $ciclo->label);
    }

    public function getFromSession(): ?Ciclo
    {
        if (!Session::has(self::SESSION_KEY)) {
            return null;
        }
        return $this->findByLabel(Session::get(self::SESSION_KEY));
    }

    public function clearSession(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function getAllForSelector(): \Illuminate\Support\Collection
    {
        return Ciclo::activo()
            ->orderByDesc('inicial')
            ->orderByDesc('final')
            ->orderByDesc('periodo')
            ->get();
    }
}