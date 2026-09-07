<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\FirebirdSync;
use App\Models\User;
use App\Services\SyncStrategies\CustomSyncStrategy;
use App\Services\SyncStrategies\CycleDirectSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirebirdSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['role' => Role::Admin]);
    }

    // =====================================================================
    // 1. Tablas ciclo: orden correcto de TABLAS_CICLO_DIRECTO
    // =====================================================================

    public function test_ciclo_directo_order_starts_with_ciclos(): void
    {
        $reflection = new \ReflectionClass(CycleDirectSync::class);
        $const = $reflection->getConstant('TABLAS_CICLO_DIRECTO');

        $this->assertIsArray($const);
        $this->assertNotEmpty($const);
        $this->assertEquals('CICLOS', $const[0], 'CICLOS debe ser la primera tabla para respetar FK constraints');
    }

    public function test_ciclo_directo_order_grupos_after_ciclos(): void
    {
        $reflection = new \ReflectionClass(CycleDirectSync::class);
        $const = $reflection->getConstant('TABLAS_CICLO_DIRECTO');

        $ciclosIndex = array_search('CICLOS', $const);
        $gruposIndex = array_search('GRUPOS', $const);

        $this->assertNotFalse($ciclosIndex);
        $this->assertNotFalse($gruposIndex);
        $this->assertGreaterThan($ciclosIndex, $gruposIndex, 'GRUPOS debe ir después de CICLOS');
    }

    public function test_ciclo_directo_order_cursos_after_ciclos(): void
    {
        $reflection = new \ReflectionClass(CycleDirectSync::class);
        $const = $reflection->getConstant('TABLAS_CICLO_DIRECTO');

        $ciclosIndex = array_search('CICLOS', $const);
        $cursosIndex = array_search('CURSOS', $const);

        $this->assertGreaterThan($ciclosIndex, $cursosIndex, 'CURSOS debe ir después de CICLOS');
    }

    public function test_ciclo_directo_order_cursos_det_after_cursos(): void
    {
        $reflection = new \ReflectionClass(CycleDirectSync::class);
        $const = $reflection->getConstant('TABLAS_CICLO_DIRECTO');

        $cursosIndex = array_search('CURSOS', $const);
        $cursosDetIndex = array_search('CURSOS_DET', $const);

        $this->assertGreaterThan($cursosIndex, $cursosDetIndex, 'CURSOS_DET debe ir después de CURSOS');
    }

    public function test_ciclo_directo_order_horarios_last(): void
    {
        $reflection = new \ReflectionClass(CycleDirectSync::class);
        $const = $reflection->getConstant('TABLAS_CICLO_DIRECTO');

        $horariosIndex = array_search('HORARIOS_DET', $const);
        $lastIndex = count($const) - 1;

        $this->assertEquals($lastIndex, $horariosIndex, 'HORARIOS_DET debe ser la última tabla (depende de más FKs)');
    }

    // =====================================================================
    // 2. Dependencias FK: validación de CustomSyncStrategy
    // =====================================================================

    public function test_dependencies_map_covers_all_synced_tables(): void
    {
        $deps = CustomSyncStrategy::TABLE_DEPENDENCIES;

        // Todas las tablas que tienen dependencias FK deben estar definidas
        $expectedTables = ['GRUPOS', 'CURSOS', 'CURSOS_DET', 'HORARIOS_DET',
            'ALUMNOS_NIVELES', 'ALUMNOS_GRUPOS'];

        foreach ($expectedTables as $table) {
            $this->assertArrayHasKey($table, $deps, "Tabla {$table} debe tener dependencias definidas");
        }
    }

    public function test_grupos_requires_ciclos(): void
    {
        $deps = CustomSyncStrategy::TABLE_DEPENDENCIES;
        $this->assertContains('CICLOS', $deps['GRUPOS']);
    }

    public function test_horarios_det_requires_grupos_and_ciclos(): void
    {
        $deps = CustomSyncStrategy::TABLE_DEPENDENCIES;
        $this->assertContains('GRUPOS', $deps['HORARIOS_DET']);
        $this->assertContains('CICLOS', $deps['HORARIOS_DET']);
    }

    public function test_alumnos_grupos_requires_alumnos_and_grupos(): void
    {
        $deps = CustomSyncStrategy::TABLE_DEPENDENCIES;
        $this->assertContains('ALUMNOS', $deps['ALUMNOS_GRUPOS']);
        $this->assertContains('GRUPOS', $deps['ALUMNOS_GRUPOS']);
    }

    public function test_validate_dependencies_static_detects_missing(): void
    {
        // Selecting HORARIOS_DET without GRUPOS, PROFESORES, etc.
        $warnings = CustomSyncStrategy::validateDependenciesStatic(['HORARIOS_DET']);

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('HORARIOS_DET', $warnings[0]);
    }

    public function test_validate_dependencies_static_no_warnings_when_complete(): void
    {
        // Selecting all tables — no missing dependencies
        $allTables = array_keys(CustomSyncStrategy::TABLE_STRATEGY_MAP);
        $warnings = CustomSyncStrategy::validateDependenciesStatic($allTables);

        $this->assertEmpty($warnings);
    }

    public function test_validate_dependencies_static_no_warnings_for_independent_tables(): void
    {
        // Independent catalog tables should have no warnings
        $warnings = CustomSyncStrategy::validateDependenciesStatic([
            'CFGSEDES', 'CFGNIVELES', 'CFGTURNOS', 'CICLOS',
        ]);

        $this->assertEmpty($warnings);
    }

    public function test_validate_dependencies_static_partial_selection(): void
    {
        // GRUPOS needs CICLOS, CFGNIVELES, CFGTURNOS, CFGSEDES
        $warnings = CustomSyncStrategy::validateDependenciesStatic(['GRUPOS']);

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('GRUPOS', $warnings[0]);
    }

    // =====================================================================
    // 3. Rutas: acceso al dashboard
    // =====================================================================

    public function test_firebird_index_requires_auth(): void
    {
        $response = $this->get(route('firebird.index'));

        $response->assertRedirect();
    }

    public function test_firebird_index_renders_for_admin(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('firebird.index'));

        $response->assertOk();
        $response->assertViewIs('firebird.index');
    }

    public function test_firebird_start_requires_admin(): void
    {
        $regularUser = User::factory()->create(['role' => Role::Operator]);

        $response = $this->actingAs($regularUser)->post(route('firebird.start'), [
            'operation' => 'sync_custom',
            'tables' => ['CFGSEDES'],
        ]);

        $response->assertForbidden();
    }

    public function test_firebird_start_accepts_empty_tables(): void
    {
        // tables is nullable|array — empty array is valid (sync_custom with no tables
        // will be caught by CustomSyncStrategy at runtime)
        $response = $this->actingAs($this->adminUser)->post(route('firebird.start'), [
            'operation' => 'sync_custom',
            'tables' => [],
        ]);

        $response->assertRedirect();
    }

    public function test_firebird_start_rejects_invalid_operation(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('firebird.start'), [
            'operation' => 'invalid_operation',
            'tables' => ['CFGSEDES'],
        ]);

        $response->assertSessionHasErrors('operation');
    }

    public function test_firebird_start_creates_sync_record(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('firebird.start'), [
            'operation' => 'sync_custom',
            'tables' => ['CFGSEDES', 'CFGNIVELES'],
            'skip_existing' => true,
        ]);

        $response->assertRedirect();
        // The sync record is created; status may change to 'failed' if the job
        // runs synchronously and can't connect to Firebird — that's expected in tests.
        $this->assertDatabaseHas('firebird_syncs', [
            'operation' => 'sync_custom',
        ]);
    }

    // =====================================================================
    // 4. Strategy map: todas las tablas tienen estrategia asignada
    // =====================================================================

    public function test_all_catalog_tables_have_strategy(): void
    {
        $strategyMap = CustomSyncStrategy::TABLE_STRATEGY_MAP;

        $expectedCatalogs = ['CFGSEDES', 'CFGNIVELES', 'CFGTURNOS', 'CICLOS',
            'CFGPLANES_MST', 'CFGPLANES_DET', 'CFGSESIONES', 'CFGTIPOSEVALUACION',
            'EMPLEADOS_CONTRATOS_CAT', 'ALUMNOS', 'PROFESORES'];

        foreach ($expectedCatalogs as $table) {
            $this->assertArrayHasKey($table, $strategyMap);
            $this->assertEquals('catalog', $strategyMap[$table], "{$table} debe ser 'catalog'");
        }
    }

    public function test_all_cycle_tables_have_cycle_strategy(): void
    {
        $strategyMap = CustomSyncStrategy::TABLE_STRATEGY_MAP;

        $expectedCycle = ['GRUPOS', 'ALUMNOS_GRUPOS', 'HORARIOS_DET', 'CURSOS', 'CURSOS_DET'];

        foreach ($expectedCycle as $table) {
            $this->assertArrayHasKey($table, $strategyMap);
            $this->assertEquals('cycle', $strategyMap[$table], "{$table} debe ser 'cycle'");
        }
    }

    public function test_alumnos_only_has_niveles(): void
    {
        $strategyMap = CustomSyncStrategy::TABLE_STRATEGY_MAP;

        // Solo ALUMNOS_NIVELES queda en la estrategia 'alumnos'
        $alumnosTables = array_filter($strategyMap, fn($v) => $v === 'alumnos');
        $this->assertEquals(['ALUMNOS_NIVELES'], array_keys($alumnosTables));
    }

    public function test_kardex_is_excluded_from_sync(): void
    {
        $strategyMap = CustomSyncStrategy::TABLE_STRATEGY_MAP;

        $this->assertArrayNotHasKey('ALUMNOS_KARDEX', $strategyMap,
            'ALUMNOS_KARDEX debe estar excluido del sync');
    }

    // =====================================================================
    // 5. FirebirdSync model
    // =====================================================================

    public function test_firebird_sync_model_operation_label(): void
    {
        // Known operations get Spanish labels
        $sync = new FirebirdSync(['operation' => 'sync_ciclo']);
        $this->assertEquals('Sincronizar Ciclo', $sync->operationLabel);

        $sync = new FirebirdSync(['operation' => 'sync_catalogos']);
        $this->assertEquals('Sincronizar Catálogos', $sync->operationLabel);

        $sync = new FirebirdSync(['operation' => 'sync_all']);
        $this->assertEquals('Sincronización Completa', $sync->operationLabel);

        // Unknown operation falls back to raw value
        $sync = new FirebirdSync(['operation' => 'sync_custom']);
        $this->assertEquals('sync_custom', $sync->operationLabel);
    }

    public function test_firebird_sync_model_status_label(): void
    {
        $sync = new FirebirdSync(['status' => 'completed']);

        $this->assertEquals('Completado', $sync->statusLabel);
    }

    // =====================================================================
    // 6. Controller: ALUMNOS_GRUPOS está en ciclo, KARDEX no aparece
    // =====================================================================

    private function getCatalogGroups(): array
    {
        $controller = new \App\Http\Controllers\FirebirdController();
        $reflection = new \ReflectionMethod($controller, 'getCatalogGroups');
        $reflection->setAccessible(true);

        return $reflection->invoke($controller);
    }

    public function test_alumnos_grupos_in_ciclo_section(): void
    {
        $groups = $this->getCatalogGroups();

        // ALUMNOS_GRUPOS debe estar en la sección ciclo
        $cicloTables = [];
        foreach ($groups['ciclo'] as $groupName => $tables) {
            foreach ($tables as $table) {
                $cicloTables[] = $table['fb'];
            }
        }
        $this->assertContains('ALUMNOS_GRUPOS', $cicloTables,
            'ALUMNOS_GRUPOS debe estar en la sección ciclo (filtrado por ciclo)');
    }

    public function test_kardex_not_in_any_section(): void
    {
        $groups = $this->getCatalogGroups();

        $allTables = [];
        foreach ($groups as $phase => $sectionGroups) {
            foreach ($sectionGroups as $groupName => $tables) {
                foreach ($tables as $table) {
                    $allTables[] = $table['fb'];
                }
            }
        }
        $this->assertNotContains('ALUMNOS_KARDEX', $allTables,
            'ALUMNOS_KARDEX no debe aparecer en ninguna sección');
    }
}
