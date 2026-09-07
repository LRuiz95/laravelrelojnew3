<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EmployeeFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:devices,id'],
            'name' => ['required', 'string', 'max:24'],
            'user_id' => ['required', 'digits_between:1,9', 'unique:employees,user_id'],
            'password' => ['nullable', 'digits_between:1,8'],
            'card_number' => ['nullable', 'string', 'max:10', 'regex:/^[0-9]+$/'],
            'role' => ['required', 'in:0,13,14'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.exists' => 'El dispositivo especificado no existe.',
            'user_id.unique' => 'Ya existe un empleado con ese user_id (PIN).',
            'role.in' => 'Rol inválido. Los roles válidos son: 0 (Usuario), 13 (Supervisor), 14 (Admin).',
        ];
    }
}