@extends('layouts.admin')

@section('title', 'Agregar empleado')

@section('content')
<div class="card shadow-sm col-lg-6">
    <div class="card-body">
        <form action="{{ route('employees.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="device_id" class="form-label">Dispositivo</label>
                <select id="device_id" name="device_id" class="form-select @error('device_id') is-invalid @enderror" required>
                    <option value="">Selecciona un checador...</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}"
                                @selected(old('device_id') == $device->id || request('device_id') == $device->id)>
                            {{ $device->name }} ({{ $device->ip }})
                        </option>
                    @endforeach
                </select>
                @error('device_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Nombre completo</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" maxlength="24" required>
                <div class="form-text">Máximo 24 caracteres.</div>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row">
                <div class="col-6 mb-3">
                    <label for="user_id" class="form-label">ID (badge)</label>
                    <input type="text" id="user_id" name="user_id"
                           class="form-control @error('user_id') is-invalid @enderror"
                           value="{{ old('user_id') }}" maxlength="9" required
                           placeholder="Ej. 2089">
                    <div class="form-text">Solo números, máximo 9 dígitos. Si la persona ya existe en el catálogo, se enrolará también en el checador elegido.</div>
                    @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 mb-3">
                          <label for="password" class="form-label">Acceso por contraseña (PIN)</label>
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                              value="{{ old('password') }}" maxlength="8" inputmode="numeric" pattern="[0-9]{1,8}" placeholder="Opcional, ej. 1234">
                          <div class="form-text">Solo números, máximo 8 dígitos.</div>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 mb-3">
                    <label for="card_number" class="form-label">Código de tarjeta</label>
                    <input type="text" id="card_number" name="card_number"
                           class="form-control @error('card_number') is-invalid @enderror"
                           value="{{ old('card_number') }}" maxlength="10" inputmode="numeric" pattern="[0-9]+"
                           placeholder="Opcional">
                    @error('card_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Rol</label>
                <small class="text-muted fw-normal">0 = Usuario (acceso básico), 13 = Supervisor (ver y editar), 14 = Admin (gestión completa)</small>
                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror">
                    @foreach (\App\Models\Employee::roles() as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', 0) == $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Agregar al checador</button>
            <a href="{{ route('employees.index') }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
</div>
@endsection