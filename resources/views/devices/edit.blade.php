@extends('layouts.admin')

@section('title', "Editar {$device->name}")

@section('content')
<div class="card shadow-sm col-lg-6">
    <div class="card-body">
        <form action="{{ route('devices.update', $device) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Nombre</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                       value="{{ old('name', $device->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="ip" class="form-label">Dirección IP</label>
                <input type="text" class="form-control @error('ip') is-invalid @enderror" id="ip" name="ip"
                       value="{{ old('ip', $device->ip ?? env('ZKTECO_DEFAULT_IP')) }}" required>
                <div class="form-text">Valor actual: {{ $device->ip ?? env('ZKTECO_DEFAULT_IP', 'No configurado') }}</div>
                @error('ip') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row">
                <div class="col-6 mb-3">
                    <label for="port" class="form-label">Puerto</label>
                    <input type="number" class="form-control @error('port') is-invalid @enderror" id="port" name="port"
                           value="{{ old('port', $device->port) }}" required>
                    @error('port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 mb-3">
                    <label for="password" class="form-label">Contraseña / CLAVE</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                           name="password" value="{{ old('password', $device->password) }}">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descripción</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                          name="description" rows="2">{{ old('description', $device->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('devices.show', $device) }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
</div>
@endsection