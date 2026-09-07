<?php

declare(strict_types=1);

namespace App\Services;

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
     * 3. Último ciclo con datos en horarios_det
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
        return Ciclo::query()
            ->whereHas('horarios')
            ->latest('inicial')
            ->latest('final')
            ->latest('periodo')
            ->firstOrFail();
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

    public function getAllForSelector(): array
    {
        return Ciclo::activo()->latest()->get()
            ->map(fn (Ciclo $c) => [
                'value' => $c->label,
                'label' => $c->label . ' (' . $c->descripcion . ')',
            ])->values()->all();
    }
}