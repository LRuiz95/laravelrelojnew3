<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeviceFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'ip' => ['required', 'ip', 'unique:devices,ip'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'password' => ['required', 'string', 'min:1', 'max:20'],
            'device_name' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'ip.unique' => 'Ya existe un dispositivo con esa IP.',
            'port.between' => 'El puerto debe estar entre 1 y 65535.',
        ];
    }
}