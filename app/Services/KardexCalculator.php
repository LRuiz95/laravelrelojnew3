<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Academia\AlumnoKardex;
use App\Models\Academia\MetodoEval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KardexCalculator
{
    /**
     * Consolida las calificaciones de una asignatura según el código de evaluación
     * Equivalente a calcularKardexAsignatura() de ProyectoBase
     */
    public function calcular(array $calificaciones): array
    {
        $result = [
            'P1' => null,
            'P2' => null,
            'P3' => null,
            'CF' => null,
            'EXR' => null,
            'EXRS' => null,
            'CT' => null,
            'ESTADO' => 'PENDIENTE',
            'LITERAL' => '',
        ];

        foreach ($calificaciones as $c) {
            $val = $c['calificacion'];
            switch ($c['id_eval']) {
                case 'A': $result['P1'] = $val; break;
                case 'B': $result['P2'] = $val; break;
                case 'C': $result['P3'] = $val; break;
                case 'D': $result['CF'] = $val; break;
                case 'E':
                    if ($c['nombre_corto'] === 'EXRS') {
                        $result['EXRS'] = $val;
                    } else {
                        if ($result['EXR'] === null || $c['tipo_examen'] == 1) {
                            $result['EXR'] = $val;
                        }
                    }
                    break;
                case 'F':
                    if ($c['nombre_corto'] === 'CT') {
                        $result['CT'] = $val;
                    } else {
                        $result['EXRS'] = $val;
                    }
                    break;
                case 'G':
                    $result['CT'] = $val;
                    break;
            }
            if (!empty($c['literal'])) {
                $result['LITERAL'] = $c['literal'];
            }
        }

        if ($result['CT'] !== null) {
            $ctVal = is_numeric($result['CT']) ? (float)$result['CT'] : -1;
            if ($result['LITERAL'] === 'N') {
                $result['ESTADO'] = 'REPROBADO';
            } elseif ($result['LITERAL'] === 'S') {
                $result['ESTADO'] = 'SIN DERECHO';
            } elseif ($ctVal >= 6) {
                $result['ESTADO'] = 'APROBADO';
            } else {
                $result['ESTADO'] = 'REPROBADO';
            }
        }

        return $result;
    }

    /**
     * Calcula el kardex completo de un alumno para un ciclo
     */
    public function calcularKardexCompleto(int $numeroAlumno, int $inicial, int $final, int $periodo): array
    {
        $kardex = AlumnoKardex::with(['materia', 'metodoEval'])
            ->where('numero_alumno', $numeroAlumno)
            ->where('inicial', $inicial)
            ->where('final', $final)
            ->where('periodo', $periodo)
            ->get()
            ->groupBy('clave_asignatura')
            ->map(function (Collection $califs) {
                return $this->calcular($califs->toArray());
            });

        return [
            'materias' => $kardex,
            'resumen' => $this->calcularResumen($kardex),
        ];
    }

    /**
     * Calcula resumen general: aprobadas, reprobadas, sin derecho, pendientes
     */
    protected function calcularResumen(Collection $materias): array
    {
        $aprobadas = $materias->where('ESTADO', 'APROBADO')->count();
        $reprobadas = $materias->where('ESTADO', 'REPROBADO')->count();
        $sinDerecho = $materias->where('ESTADO', 'SIN DERECHO')->count();
        $pendientes = $materias->where('ESTADO', 'PENDIENTE')->count();

        return [
            'total' => $materias->count(),
            'aprobadas' => $aprobadas,
            'reprobadas' => $reprobadas,
            'sin_derecho' => $sinDerecho,
            'pendientes' => $pendientes,
            'promedio' => $this->calcularPromedio($materias),
        ];
    }

    protected function calcularPromedio(Collection $materias): ?float
    {
        $conCalificacion = $materias->filter(fn ($m) => $m['CT'] !== null && is_numeric($m['CT']));
        if ($conCalificacion->isEmpty()) {
            return null;
        }
        return round($conCalificacion->avg('CT'), 2);
    }

    /**
     * Obtiene el historial de un alumno (todos los ciclos)
     */
    public function getHistorialAlumno(int $numeroAlumno): array
    {
        return DB::table('alumnos_kardex')
            ->join('materias', 'alumnos_kardex.clave_asignatura', '=', 'materias.clave_asignatura')
            ->join('metodos_eval', 'alumnos_kardex.id_eval', '=', 'metodos_eval.id_eval')
            ->where('alumnos_kardex.numero_alumno', $numeroAlumno)
            ->select(
                'alumnos_kardex.*',
                'materias.nombre_asignatura',
                'metodos_eval.nombre_corto as eval_nombre',
                'metodos_eval.es_final'
            )
            ->orderBy('alumnos_kardex.inicial', 'desc')
            ->orderBy('alumnos_kardex.final', 'desc')
            ->orderBy('alumnos_kardex.periodo', 'desc')
            ->get()
            ->groupBy(function ($row) {
                return "{$row->inicial}-{$row->final}-{$row->periodo}";
            })
            ->map(function ($cicloKardex) {
                return $this->calcular($cicloKardex->toArray());
            })
            ->toArray();
    }
}