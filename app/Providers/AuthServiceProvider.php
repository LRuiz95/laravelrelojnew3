<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        App\Models\Employee::class => App\Policies\EmployeePolicy::class,
        App\Models\Device::class => App\Policies\DevicePolicy::class,
        App\Models\Academia\Alumno::class => App\Policies\AlumnoPolicy::class,
        App\Models\Academia\Curso::class => App\Policies\CursoPolicy::class,
        App\Models\Academia\Grupo::class => App\Policies\GrupoPolicy::class,
        App\Models\Academia\Plan::class => App\Policies\PlanPolicy::class,
        App\Models\Academia\Ciclo::class => App\Policies\CicloPolicy::class,
        App\Models\Academia\Profesor::class => App\Policies\ProfesorPolicy::class,
        App\Models\Academia\HorarioDet::class => App\Policies\HorarioPolicy::class,
        App\Models\Academia\Materia::class => App\Policies\MateriaPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
