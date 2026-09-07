<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CursoFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clave_curso' => ['required', 'string', 'max:20',
                'unique:cursos,clave_curso',
                fn ($attr, $value, $fail) => !$this->route('curso') || $this->route('curso')->clave_curso !== $value],
            'nombre_curso' => ['required', 'string', 'max:100'],
            'nivel' => ['required', 'string', 'max:10', 'exists:niveles,nivel'],
            'turno' => ['required', 'string', 'max:10', 'exists:turnos,turno'],
            'id_campus' => ['nullable', 'string', 'max:20', 'exists:sedes,id_campus'],
        ];
    }

    public function messages(): array
    {
        return [
            'clave_curso.unique' => 'Ya existe un curso con esa clave.',
            'nivel.exists' => 'Nivel no existente.',
            'turno.exists' => 'Turno no existente.',
            'id_campus.exists' => 'Campus no existente.',
        ];
    }
}