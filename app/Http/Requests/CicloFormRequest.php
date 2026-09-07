<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CicloFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inicial' => ['required', 'integer', 'min:2000', 'max:2100',
                'unique:ciclos,inicial',
                fn ($attr, $value, $fail) => !$this->route('ciclo') || $this->route('ciclo')->inicial !== $value],
            'final' => ['required', 'integer', 'min:2000', 'max:2100',
                'unique:ciclos,final',
                fn ($attr, $value, $fail) => !$this->route('ciclo') || $this->route('ciclo')->final !== $value],
            'periodo' => ['required', 'integer', 'min:1', 'max:4',
                'unique:ciclos,periodo',
                fn ($attr, $value, $fail) => !$this->route('ciclo') || $this->route('ciclo')->periodo !== $value],
            'descripcion' => ['nullable', 'string', 'max:100'],
            'fecha_inicial' => ['nullable', 'date'],
            'fecha_final' => ['nullable', 'date', 'after_or_equal:fecha_inicial'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'inicial.unique' => 'Ya existe un ciclo con esa inicial.',
            'final.unique' => 'Ya existe un ciclo con esa final.',
            'periodo.unique' => 'Ya existe un ciclo con ese periodo.',
            'fecha_final.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
        ];
    }
}