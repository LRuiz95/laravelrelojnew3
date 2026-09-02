<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · {{ config('app.name') }}</title>
    <script>
        // Aplica el tema antes del primer paint para evitar FOUC.
        (() => {
            try {
                const stored = localStorage.getItem('dash-theme') || 'system';
                const theme = stored === 'system'
                    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : stored;
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.setAttribute('data-theme-preference', stored);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.setAttribute('data-theme-preference', 'system');
            }
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page">
<main class="auth-wrap">
    <div class="auth-card card">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <span class="brand-mark mx-auto mb-3"><i class="bi bi-fingerprint"></i></span>
                <h1 class="h4 mb-1 fw-bold" style="color:var(--text)">{{ config('app.name') }}</h1>
                <p class="text-muted mb-0" style="font-size:13px">Acceso al panel de control</p>
            </div>
            <form action="{{ route('login.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="bi bi-envelope"></i></span>
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                    </div>
                    @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="bi bi-shield-lock"></i></span>
                        <input id="password" name="password" type="password"
                               class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                    </div>
                    @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="form-check mb-4">
                    <input id="remember" name="remember" type="checkbox" class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember" class="form-check-label text-secondary-token">Recordarme en este equipo</label>
                </div>
                <button class="btn btn-primary w-100" style="height:42px">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
                </button>
            </form>
        </div>
        <div class="card-footer text-center py-3" style="border-top:1px solid var(--border)">
            <span class="text-tertiary-token" style="font-size:12px">
                <i class="bi bi-shield-check me-1"></i>Sistema de control de asistencia biométrico
            </span>
        </div>
    </div>
</main>
</body>
</html>