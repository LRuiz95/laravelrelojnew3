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
 * Método unificado que obtiene el ciclo actual.
 * Prioridad: URL param ?ciclo_principal= → sesión → default activo.
 * Si viene por URL, guarda en sesión para consistencia.
 */
public function getCurrent(Request $request): Ciclo
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

    // 3. Default: último ciclo activo
    return $this->getDefaultCiclo();
}

/**
 * Wrapper para backward compatibility. Usa getCurrent() internamente.
 */
public function resolve(Request $request): Ciclo
{
    return $this->getCurrent($request);
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
     * Wrapper para backward compatibility (header/global).
     * Retorna null en lugar de lanzar excepción.
     */
    public function current(?Request $request = null): ?Ciclo
    {
        try {
            return $this->getCurrent($request ?? request());
        } catch (NoCiclosConfiguradosException $e) {
            return null;
        }
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