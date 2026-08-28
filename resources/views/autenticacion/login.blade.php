<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | Oficina del Agua</title>

    @vite([
        'resources/css/adminlte.css',
        'resources/js/adminlte.js'
    ])
</head>

<body class="login-page bg-body-secondary">

<div class="login-box">

    <div class="card card-outline card-primary shadow">

        <div class="card-header text-center">
            <h1 class="h4 mb-0">
                <i class="bi bi-droplet-fill me-2"></i>
                Oficina del Agua
            </h1>
        </div>

        <div class="card-body">

            <p class="login-box-msg">
                Ingrese sus credenciales para acceder al sistema
            </p>

            {{-- Errores generales de autenticación --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.procesar') }}" method="POST">

                @csrf

                {{-- Correo electrónico --}}
                <div class="input-group mb-3">
                    <input
                        type="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="Correo electrónico"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        autofocus
                        required
                    >

                    <div class="input-group-text">
                        <span class="bi bi-envelope"></span>
                    </div>
                </div>

                {{-- Contraseña --}}
                <div class="input-group mb-3">
                    <input
                        type="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="Contraseña"
                        autocomplete="current-password"
                        required
                    >

                    <div class="input-group-text">
                        <span class="bi bi-lock-fill"></span>
                    </div>
                </div>

                {{-- Botón --}}
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Iniciar sesión
                    </button>
                </div>

            </form>

        </div>

        <div class="card-footer text-center text-body-secondary">
            Sistema de Gestión de la Oficina del Agua
        </div>

    </div>

</div>

</body>
</html>