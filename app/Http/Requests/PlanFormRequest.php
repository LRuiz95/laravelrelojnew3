<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PlanFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_plan' => ['required', 'integer',
                'unique:planes,id_plan',
                fn ($attr, $value, $fail) => !$this->route('plan') || $this->route('plan')->id_plan !== $value],
            'nombre_plan' => ['required', 'string', 'max:100'],
            'nivel' => ['required', 'string', 'max:10', 'exists:niveles,nivel'],
            'modalidad' => ['nullable', 'string', 'max:20'],
            'duracion_semestres' => ['nullable', 'integer', 'min:1', 'max:12'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_plan.unique' => 'Ya existe un plan con ese ID.',
            'nivel.exists' => 'Nivel no existente.',
            'duracion_semestres.between' => 'Duración debe estar entre 1 y 12 semestres.',
        ];
    }
}