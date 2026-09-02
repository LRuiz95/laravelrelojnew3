@extends('layouts.admin')

@section('title', 'Registrar dispositivo')

@section('content')
<div class="card shadow-sm col-lg-6">
    <div class="card-body">
        <form action="{{ route('devices.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Nombre</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                       value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="ip" class="form-label">Dirección IP</label>
                <input type="text" class="form-control @error('ip') is-invalid @enderror" id="ip" name="ip"
                       value="{{ old('ip', env('ZKTECO_DEFAULT_IP')) }}" required placeholder="192.168.1.x (deja vacío para usar configuración por defecto)">
                @error('ip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Deja vacío para usar la IP por defecto: {{ env('ZKTECO_DEFAULT_IP') }}</div>
            </div>

            <div class="row">
                <div class="col-6 mb-3">
                    <label for="port" class="form-label">Puerto</label>
                    <input type="number" class="form-control @error('port') is-invalid @enderror" id="port" name="port"
                           value="{{ old('port', env('ZKTECO_DEFAULT_PORT', 4370)) }}" required>
                    @error('port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 mb-3">
                    <label for="password" class="form-label">Contraseña / CLAVE</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                           name="password" value="{{ old('password', '') }}" placeholder="Vacía = 0">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descripción</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                          name="description" rows="2">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Guardar dispositivo</button>
            <a href="{{ route('devices.index') }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
</div>
@endsection