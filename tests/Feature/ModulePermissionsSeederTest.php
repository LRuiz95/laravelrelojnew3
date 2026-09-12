<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulePermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_core_module_permission_definitions(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class])->assertOk();

        $this->assertDatabaseHas('modules', ['slug' => 'empleados']);
        $this->assertDatabaseHas('modules', ['slug' => 'incidencias']);
        $this->assertDatabaseHas('modules', ['slug' => 'puntualidad']);

        $empleadosModuleId = Module::query()->where('slug', 'empleados')->value('id');
        $this->assertDatabaseHas('permissions', [
            'module_id' => $empleadosModuleId,
            'slug' => 'empleados.view',
            'action' => 'view',
        ]);

        $incidenciasModuleId = Module::query()->where('slug', 'incidencias')->value('id');
        $this->assertDatabaseHas('permissions', [
            'module_id' => $incidenciasModuleId,
            'slug' => 'incidencias.create',
            'action' => 'create',
        ]);

        $this->assertTrue(Permission::query()->where('slug', 'puntualidad.view')->exists());
    }
}
