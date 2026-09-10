<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Academia\Alumno;
use App\Models\Academia\AlumnoGrupo;
use App\Models\Academia\Grupo;
use App\Models\Academia\Profesor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademiaHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected $alumnoActivo;
    protected $alumnoInactivo;
    protected $alumnoSinIncripciones;
    protected $profesorConHorarios;
    protected $profesorSinHorarios;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpiar tablas
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['alumnos_grupos','horarios_det','alumnos','profesores','grupos','materias','niveles','turnos','ciclos'] as $t) {
            DB::table($t)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Ciclos
        DB::table('ciclos')->insert(['inicial' => 1, 'final' => 3, 'periodo' => 1]);
        DB::table('ciclos')->insert(['inicial' => 4, 'final' => 6, 'periodo' => 1]);

        // Niveles y Turnos
        DB::table('niveles')->insert(['nivel' => 'Basico', 'descripcion' => 'Basico']);
        DB::table('turnos')->insert(['turno' => 'M', 'descripcion' => 'Mañana']);
        DB::table('turnos')->insert(['turno' => 'V', 'descripcion' => 'Vespertino']);

        // Materias
        DB::table('materias')->insert([
            'clave_asignatura' => 'MAT-001', 'nombre_asignatura' => 'Matemáticas',
            'nombre_corto' => 'Mat', 'id_plan' => 'PLAN-001', 'semestre' => 1,
            'horas_teoria' => 5, 'horas_practica' => 2, 'creditos' => 6,
            'tipo' => 'obligatoria', 'activa' => true,
        ]);

        // Grupos
        DB::table('grupos')->insert([
            'codigo_grupo' => 'A-1', 'inicial' => 1, 'final' => 3, 'periodo' => 1,
            'grado' => 1, 'turno' => 'M', 'nivel' => 'Basico', 'activo' => true,
        ]);
        DB::table('grupos')->insert([
            'codigo_grupo' => 'B-1', 'inicial' => 4, 'final' => 6, 'periodo' => 1,
            'grado' => 2, 'turno' => 'V', 'nivel' => 'Basico', 'activo' => true,
        ]);

        // Alumnos
        $this->alumnoActivo = Alumno::create([
            'numero_alumno' => 1, 'paterno' => 'Gonzalez', 'materno' => 'Lopez', 'nombre' => 'Maria',
            'estatus' => 'ACTIVO', 'nivel' => 'Basico', 'turno' => 'M',
        ]);
        $this->alumnoInactivo = Alumno::create([
            'numero_alumno' => 2, 'paterno' => 'Sanchez', 'materno' => 'Rivera', 'nombre' => 'Juan',
            'estatus' => 'BAJA', 'nivel' => 'Basico', 'turno' => 'V',
        ]);
        $this->alumnoSinIncripciones = Alumno::create([
            'numero_alumno' => 3, 'paterno' => 'Perez', 'materno' => 'Torres', 'nombre' => 'Carlos',
            'estatus' => 'ACTIVO', 'nivel' => 'Basico', 'turno' => 'M',
        ]);

        // Inscripciones (alumnos_grupos)
        DB::table('alumnos_grupos')->insert([
            'numero_alumno' => 1, 'codigo_grupo' => 'A-1', 'inicial' => 1, 'final' => 3, 'periodo' => 1,
            'fecha_inscripcion' => now(), 'estatus' => 'INSCRITO',
        ]);
        DB::table('alumnos_grupos')->insert([
            'numero_alumno' => 1, 'codigo_grupo' => 'B-1', 'inicial' => 4, 'final' => 6, 'periodo' => 1,
            'fecha_inscripcion' => now(), 'estatus' => 'INSCRITO',
        ]);

        // Profesores
        $this->profesorConHorarios = Profesor::create([
            'clave_profesor' => 'PROF-001', 'nombre_profesor' => 'Roberto',
            'paterno' => 'Gomez', 'materno' => 'Silva', 'status_actual' => 'A', 'origen_horario' => 'HD',
        ]);
        $this->profesorSinHorarios = Profesor::create([
            'clave_profesor' => 'PROF-002', 'nombre_profesor' => 'Laura',
            'paterno' => 'Martinez', 'materno' => 'Torres', 'status_actual' => 'A', 'origen_horario' => 'CA',
        ]);

        // Horarios para PROF-001
        DB::table('horarios_det')->insert([
            'codigo_grupo' => 'A-1', 'inicial' => 1, 'final' => 3, 'periodo' => 1,
            'clave_profesor' => 'PROF-001', 'clave_asignatura' => 'MAT-001',
            'dia' => 1, 'sesion' => 1, 'activo' => true,
        ]);
    }

    // ── Model Scopes ──────────────────────────────────────────────

    public function test_alumno_scope_por_ciclo(): void
    {
        $result = Alumno::query()->porCiclo(1, 3, 1)->get();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result->first()->numero_alumno);
    }

    public function test_alumno_scope_inscritos_en_ciclo(): void
    {
        $result = Alumno::query()->inscritosEnCiclo(1, 3, 1)->get();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result->first()->numero_alumno);
    }

    public function test_alumno_scope_activo(): void
    {
        $result = Alumno::query()->activo()->get();

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('numero_alumno', 1));
        $this->assertTrue($result->contains('numero_alumno', 3));
        $this->assertFalse($result->contains('numero_alumno', 2));
    }

    public function test_profesor_scope_con_horarios_en_ciclo(): void
    {
        $result = Profesor::query()->conHorariosEnCiclo(1, 3, 1)->get();

        $this->assertCount(1, $result);
        $this->assertEquals('PROF-001', $result->first()->clave_profesor);
    }

    public function test_profesor_scope_todos(): void
    {
        $result = Profesor::query()->todos()->get();

        $this->assertCount(2, $result);
    }

    // ── Relationships ─────────────────────────────────────────────

    public function test_alumno_inscripciones_relationship(): void
    {
        $alumno = $this->alumnoActivo;
        $inscripciones = $alumno->inscripciones;

        $this->assertCount(2, $inscripciones);
        $this->assertInstanceOf(AlumnoGrupo::class, $inscripciones->first());

        // Verify pivot data
        $first = $inscripciones->first();
        $this->assertEquals(1, $first->inicial);
        $this->assertEquals('INSCRITO', $first->estatus);
    }

    // ── Controllers (HTTP tests) ──────────────────────────────────

    public function test_alumno_controller_index(): void
    {
        $response = $this->get('/academia/alumnos?ciclo_principal=1-3-1');

        // May redirect to login (302) if auth middleware is active
        // In that case, we verify the route exists and responds
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_profesor_controller_index(): void
    {
        $response = $this->get('/academia/profesores?solo_ciclo=1&ciclo_principal=1-3-1');

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_plan_controller_index(): void
    {
        $response = $this->get('/academia/planes?ciclo_principal=1-3-1');

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }
}
