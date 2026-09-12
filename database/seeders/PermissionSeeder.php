<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'slug' => 'empleados',
                'name' => 'Empleados',
                'description' => 'Administración del catálogo de empleados.',
                'active' => true,
            ],
            [
                'slug' => 'incidencias',
                'name' => 'Incidencias',
                'description' => 'Módulo de incidencias y autorizaciones.',
                'active' => true,
            ],
            [
                'slug' => 'puntualidad',
                'name' => 'Puntualidad',
                'description' => 'Revisión de retrasos, llegadas tempranas y salidas.',
                'active' => true,
            ],
            [
                'slug' => 'asistencias',
                'name' => 'Asistencias',
                'description' => 'Consulta y exportación de asistencias.',
                'active' => true,
            ],
            [
                'slug' => 'academia',
                'name' => 'Academia',
                'description' => 'Módulo académico y ciclos.',
                'active' => true,
            ],
            [
                'slug' => 'dispositivos',
                'name' => 'Dispositivos',
                'description' => 'Administración de checadores y sincronización.',
                'active' => true,
            ],
        ];

        foreach ($modules as $moduleData) {
            $module = Module::query()->firstOrCreate(
                ['slug' => $moduleData['slug']],
                $moduleData
            );

            $definitions = [
                'empleados' => [
                    ['slug' => 'empleados.view', 'action' => 'view', 'name' => 'Ver empleados'],
                    ['slug' => 'empleados.create', 'action' => 'create', 'name' => 'Crear empleados'],
                    ['slug' => 'empleados.update', 'action' => 'update', 'name' => 'Actualizar empleados'],
                    ['slug' => 'empleados.delete', 'action' => 'delete', 'name' => 'Eliminar empleados'],
                ],
                'incidencias' => [
                    ['slug' => 'incidencias.view', 'action' => 'view', 'name' => 'Ver incidencias'],
                    ['slug' => 'incidencias.create', 'action' => 'create', 'name' => 'Crear incidencias'],
                    ['slug' => 'incidencias.update', 'action' => 'update', 'name' => 'Actualizar incidencias'],
                    ['slug' => 'incidencias.approve', 'action' => 'approve', 'name' => 'Autorizar incidencias'],
                ],
                'puntualidad' => [
                    ['slug' => 'puntualidad.view', 'action' => 'view', 'name' => 'Ver puntualidad'],
                    ['slug' => 'puntualidad.export', 'action' => 'export', 'name' => 'Exportar puntualidad'],
                ],
                'asistencias' => [
                    ['slug' => 'asistencias.view', 'action' => 'view', 'name' => 'Ver asistencias'],
                    ['slug' => 'asistencias.export', 'action' => 'export', 'name' => 'Exportar asistencias'],
                ],
                'academia' => [
                    ['slug' => 'academia.view', 'action' => 'view', 'name' => 'Ver academia'],
                    ['slug' => 'academia.create', 'action' => 'create', 'name' => 'Crear registros académicos'],
                    ['slug' => 'academia.update', 'action' => 'update', 'name' => 'Actualizar registros académicos'],
                ],
                'dispositivos' => [
                    ['slug' => 'dispositivos.view', 'action' => 'view', 'name' => 'Ver dispositivos'],
                    ['slug' => 'dispositivos.sync', 'action' => 'sync', 'name' => 'Sincronizar dispositivos'],
                ],
            ];

            foreach ($definitions[$module->slug] ?? [] as $permissionData) {
                Permission::query()->firstOrCreate(
                    ['module_id' => $module->id, 'slug' => $permissionData['slug']],
                    [
                        'module_id' => $module->id,
                        'slug' => $permissionData['slug'],
                        'action' => $permissionData['action'],
                        'name' => $permissionData['name'],
                    ]
                );
            }
        }
    }
}
