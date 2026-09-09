<?php

use App\Http\Controllers\Academia\DashboardController as AcademiaDashboardController;
use App\Http\Controllers\Academia\CicloController as AcademiaCicloController;
use App\Http\Controllers\Academia\CursoController as AcademiaCursoController;
use App\Http\Controllers\Academia\PlanController as AcademiaPlanController;
use App\Http\Controllers\Academia\GrupoController as AcademiaGrupoController;
use App\Http\Controllers\Academia\AlumnoController as AcademiaAlumnoController;
use App\Http\Controllers\Academia\ProfesorController as AcademiaProfesorController;
use App\Http\Controllers\Academia\HorarioController as AcademiaHorarioController;
use App\Http\Controllers\Academia\KardexController as AcademiaKardexController;
use App\Http\Controllers\Academia\ApiController as AcademiaApiController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceSyncController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FingerprintController;
use App\Http\Controllers\FirebirdController;
use App\Http\Controllers\OperationsController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('kpis/json', [DashboardController::class, 'kpisJson'])->name('dashboard.kpisJson');

    // Academia routes
    Route::prefix('academia')->name('academia.')->group(function () {
        Route::get('/', [AcademiaDashboardController::class, 'index'])->name('dashboard');

        // Ciclos
        Route::resource('ciclos', AcademiaCicloController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('ciclos/{ciclo}/activo', [AcademiaCicloController::class, 'setActivo'])->name('ciclos.activo');

        // Grupos
        Route::resource('grupos', AcademiaGrupoController::class)->only(['index', 'show']);
        Route::get('grupos/{grupo}/asistencia', [AcademiaGrupoController::class, 'asistencia'])->name('grupos.asistencia');
        Route::post('grupos/{grupo}/asistencia', [AcademiaGrupoController::class, 'guardarAsistencia'])->name('grupos.asistencia.guardar');

        // Alumnos
        Route::resource('alumnos', AcademiaAlumnoController::class)->only(['index', 'show']);
        Route::get('alumnos/{alumno}/kardex', [AcademiaAlumnoController::class, 'kardex'])->name('alumnos.kardex');
        Route::get('alumnos/{alumno}/historial', [AcademiaAlumnoController::class, 'historial'])->name('alumnos.historial');

        // Profesores
        Route::resource('profesores', AcademiaProfesorController::class)->only(['index', 'show']);
        Route::get('profesores/{profesor}/horario', [AcademiaProfesorController::class, 'horario'])->name('profesores.horario');

        // Horarios
        Route::prefix('horarios')->name('horarios.')->group(function () {
            Route::get('clase', [AcademiaHorarioController::class, 'clase'])->name('clase');
            Route::get('profesor', [AcademiaHorarioController::class, 'profesor'])->name('profesor');
            Route::get('aula', [AcademiaHorarioController::class, 'aula'])->name('aula');
            Route::get('base', [AcademiaHorarioController::class, 'base'])->name('base');
            Route::get('persona', [AcademiaHorarioController::class, 'persona'])->name('persona');
        });

        // Kardex
        Route::prefix('kardex')->name('kardex.')->group(function () {
            Route::get('/', [App\Http\Controllers\Academia\KardexController::class, 'index'])->name('index');
            Route::get('show', [App\Http\Controllers\Academia\KardexController::class, 'show'])->name('show');
            Route::get('historial', [App\Http\Controllers\Academia\KardexController::class, 'historial'])->name('historial');
            Route::get('print', [App\Http\Controllers\Academia\KardexController::class, 'print'])->name('print');
        });

        // Cursos
        Route::resource('cursos', AcademiaCursoController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('cursos/{curso}/materia', [AcademiaCursoController::class, 'addMateria'])->name('cursos.materia.add');
        Route::delete('cursos/{curso}/materia/{materia}', [AcademiaCursoController::class, 'removeMateria'])->name('cursos.materia.remove');

        // Planes
        Route::resource('planes', AcademiaPlanController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
    });

    // API Routes for AJAX
    Route::prefix('api/academia')->name('api.academia.')->group(function () {
        Route::get('grupos-por-ciclo', [AcademiaApiController::class, 'gruposPorCiclo'])->name('grupos-por-ciclo');
        Route::get('alumnos-por-grupo', [AcademiaApiController::class, 'alumnosPorGrupo'])->name('alumnos-por-grupo');
        Route::get('ciclos-disponibles', [AcademiaApiController::class, 'ciclosDisponibles'])->name('ciclos-disponibles');
        Route::get('planes-por-nivel', [AcademiaApiController::class, 'planesPorNivel'])->name('planes-por-nivel');
        Route::get('materias-por-plan', [AcademiaApiController::class, 'materiasPorPlan'])->name('materias-por-plan');
        Route::get('metodos-eval', [AcademiaApiController::class, 'metodosEval'])->name('metodos-eval');
        Route::get('niveles', [AcademiaApiController::class, 'niveles'])->name('niveles');
        Route::get('turnos', [AcademiaApiController::class, 'turnos'])->name('turnos');
        Route::get('sedes', [AcademiaApiController::class, 'sedes'])->name('sedes');
        Route::get('horario-base', [AcademiaApiController::class, 'horarioBase'])->name('horario-base');
        Route::get('grupo-detalle', [AcademiaApiController::class, 'grupoDetalle'])->name('grupo-detalle');
    });

    // Firebird Sync — orden: rutas literales antes que params para evitar captura
    Route::prefix('firebird')->name('firebird.')->group(function () {
        Route::get('/', [FirebirdController::class, 'index'])->name('index');
        Route::post('/start', [FirebirdController::class, 'startSync'])->middleware('admin')->name('start');
        Route::post('/execute-pending', [FirebirdController::class, 'executePending'])->middleware('admin')->name('execute-pending');
        Route::get('sync/{sync}', [FirebirdController::class, 'sync'])->name('sync');
        Route::get('/{sync}/status', [FirebirdController::class, 'status'])->name('status');
        Route::post('/{sync}/cancel', [FirebirdController::class, 'cancel'])->middleware('admin')->name('cancel');
        Route::post('/{sync}/retry', [FirebirdController::class, 'retry'])->middleware('admin')->name('retry');
        Route::delete('/{sync}', [FirebirdController::class, 'destroy'])->middleware('admin')->name('delete');
    });

    // Ciclo selector - AJAX endpoint
    Route::post('/academia/set-ciclo', function (\Illuminate\Http\Request $request) {
        $label = $request->input('ciclo_label');
        if ($label) {
            session(\App\Services\CicloActualService::SESSION_KEY, $label);
        } else {
            session()->forget(\App\Services\CicloActualService::SESSION_KEY);
        }
        return response()->json(['success' => true, 'ciclo' => $label]);
    })->middleware('auth')->name('academia.set-ciclo');

    Route::prefix('devices')->name('devices.')->group(function () {
        Route::get('/', [DeviceController::class, 'index'])->name('index');
        Route::get('/create', [DeviceController::class, 'create'])->middleware('admin')->name('create');
        Route::post('/', [DeviceController::class, 'store'])->middleware('admin')->name('store');
        Route::get('/{device}', [DeviceController::class, 'show'])->name('show');
        Route::get('/{device}/sync-status', [DeviceController::class, 'syncStatus'])->name('sync-status');
        Route::get('/{device}/refresh-data', [DeviceController::class, 'refreshData'])->name('refresh-data');
        Route::get('/{device}/progress', [DeviceController::class, 'progress'])->name('progress');
        Route::get('/{device}/edit', [DeviceController::class, 'edit'])->middleware('admin')->name('edit');
        Route::put('/{device}', [DeviceController::class, 'update'])->middleware('admin')->name('update');
        Route::delete('/{device}', [DeviceController::class, 'destroy'])->middleware('admin')->name('destroy');

        Route::post('/{device}/check-status', [DeviceController::class, 'checkStatus'])->name('check-status');
        Route::middleware(['admin', 'throttle:30,1'])->group(function () {
            Route::post('/deduplicate', [DeviceController::class, 'deduplicate'])->name('deduplicate');
            Route::post('/{device}/sync-users', [DeviceSyncController::class, 'syncUsers'])->name('sync-users');
            Route::post('/{device}/sync-fingerprints', [DeviceSyncController::class, 'syncFingerprints'])->name('sync-fingerprints');
            Route::post('/{device}/sync-attendances', [DeviceSyncController::class, 'syncAttendances'])->name('sync-attendances');
            Route::post('/{device}/sync-all', [DeviceSyncController::class, 'syncAll'])->name('sync-all');
            Route::post('/{device}/employees/{employee}/upload-fingerprints', [FingerprintController::class, 'uploadFingerprintsOnDevice'])->name('employees.upload-fingerprints');
            Route::delete('/{device}/employees/{employee}', [FingerprintController::class, 'removeFromDevice'])->name('employees.remove');
            Route::post('/{device}/set-time', [DeviceSyncController::class, 'setTime'])->name('set-time');
            Route::post('/{device}/sync-now', [DeviceController::class, 'syncNow'])->name('sync-now');
            Route::post('/{device}/clear-attendance', [DeviceSyncController::class, 'clearAttendance'])->name('clear-attendance');
            Route::post('/{device}/restore', [DeviceSyncController::class, 'restore'])->name('restore');
        });
    });

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/search', [EmployeeController::class, 'search'])->name('search');
        Route::get('/create', [EmployeeController::class, 'create'])->middleware('admin')->name('create');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->middleware('admin')->name('edit');
Route::middleware('admin')->group(function () {
            Route::post('/', [EmployeeController::class, 'store'])->name('store')->middleware('throttle:30,1');
            Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update')->middleware('throttle:30,1');
            Route::post('/{employee}/card', [EmployeeController::class, 'updateCard'])->name('update-card')->middleware('throttle:30,1');
            Route::post('/{employee}/enroll-device', [EmployeeController::class, 'enrollOnDevice'])->name('enroll-device')->middleware('throttle:30,1');
            Route::post('/{employee}/sync-devices', [EmployeeController::class, 'syncToDevices'])->name('sync-devices')->middleware('throttle:30,1');
            Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
        });
    });

    Route::get('/fingerprints', [FingerprintController::class, 'index'])->name('fingerprints.index');

    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendances/export', [AttendanceController::class, 'export'])->name('attendances.export');
    Route::get('/attendances/print', [AttendanceController::class, 'print'])->name('attendances.print');
    Route::get('/sync-queue', [OperationsController::class, 'queue'])->middleware('admin')->name('operations.queue');
    Route::get('/sync-queue/data', [OperationsController::class, 'queueData'])->middleware('admin')->name('operations.queue.data');
    Route::get('/sync-queue/data-unified', [OperationsController::class, 'queueDataUnified'])->middleware('admin')->name('operations.queue.data.unified');
    Route::post('/sync-queue/{sync}/cancel', [OperationsController::class, 'cancel'])->middleware('admin')->name('operations.cancel');
    Route::post('/sync-queue/{sync}/retry', [OperationsController::class, 'retry'])->middleware('admin')->name('operations.retry');
    Route::delete('/sync-queue/{sync}', [OperationsController::class, 'delete'])->middleware('admin')->name('operations.delete');
    Route::get('/notifications', [OperationsController::class, 'notifications'])->name('operations.notifications');
});