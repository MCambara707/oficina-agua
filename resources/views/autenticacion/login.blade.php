<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | AquaTech GT</title>

    @vite([
        'resources/css/adminlte.css',
        'resources/js/adminlte.js'
    ])
</head>

<body class="login-page aquatech-login-page">

    <main class="aquatech-login-shell">

        <div class="login-box aquatech-login-box">

            <section class="card aquatech-login-card border-0 shadow-lg">

                <div class="card-body p-4 p-md-5">

                    {{-- =====================================================
                         BRANDING
                    ====================================================== --}}
                    <div class="text-center aquatech-login-brand">

                        <img
                            src="{{ asset('img/branding/Logo_AquatechGt.png') }}"
                            alt="AquaTech GT"
                            class="aquatech-login-logo mx-auto"
                            width="1933"
                            height="506"
                        >

                        <div
                            class="aquatech-login-divider"
                            aria-hidden="true"
                        ></div>

                        <p class="aquatech-login-subtitle mb-4">
                            Ingrese sus credenciales para acceder al sistema
                        </p>

                    </div>


                    {{-- =====================================================
                         ERRORES DE AUTENTICACIÓN
                    ====================================================== --}}
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


                    {{-- =====================================================
                         FORMULARIO
                    ====================================================== --}}
                    <form
                        action="{{ route('login.procesar') }}"
                        method="POST"
                        autocomplete="off"
                        id="loginForm"
                    >

                        @csrf

                        {{-- Campos señuelo para reducir el autofill --}}
                        <input
                            type="text"
                            name="usuario_autocompletado"
                            autocomplete="username"
                            tabindex="-1"
                            aria-hidden="true"
                            class="position-absolute opacity-0"
                            style="pointer-events: none;"
                        >

                        <input
                            type="password"
                            name="password_autocompletado"
                            autocomplete="current-password"
                            tabindex="-1"
                            aria-hidden="true"
                            class="position-absolute opacity-0"
                            style="pointer-events: none;"
                        >


                        {{-- =================================================
                             CORREO
                        ================================================== --}}
                        <div class="input-group aquatech-login-field mb-3">

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="Correo"
                                autocomplete="off"
                                required
                                aria-label="Correo electrónico"
                            >

                            <span
                                class="input-group-text"
                                aria-hidden="true"
                            >
                                <i class="bi bi-envelope"></i>
                            </span>

                        </div>


                        {{-- =================================================
                             PASSWORD
                        ================================================== --}}
                        <div class="input-group aquatech-login-field mb-4">

                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Contraseña"
                                autocomplete="new-password"
                                required
                                aria-label="Contraseña"
                            >

                            <button
                                type="button"
                                class="input-group-text password-toggle"
                                id="togglePassword"
                                aria-label="Mostrar contraseña"
                                aria-controls="password"
                                aria-pressed="false"
                                title="Mostrar contraseña"
                            >
                                <i
                                    class="bi bi-eye-fill"
                                    id="togglePasswordIcon"
                                    aria-hidden="true"
                                ></i>
                            </button>

                        </div>


                        {{-- =================================================
                             INICIAR SESIÓN
                        ================================================== --}}
                        <div class="d-grid mb-3">

                            <button
                                type="submit"
                                class="btn btn-primary aquatech-login-submit"
                            >
                                <i
                                    class="bi bi-box-arrow-in-right me-2"
                                    aria-hidden="true"
                                ></i>

                                Iniciar sesión
                            </button>

                        </div>


                        {{-- =================================================
                             WHATSAPP
                        ================================================== --}}
                        <div class="d-grid">

                            <a
                                href="https://wa.me/50241163863"
                                class="btn aquatech-whatsapp-btn"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Contactarnos por WhatsApp"
                            >
                                <i
                                    class="bi bi-whatsapp me-2"
                                    aria-hidden="true"
                                ></i>

                                Contáctanos
                            </a>

                        </div>

                    </form>

                </div>

            </section>

        </div>

    </main>


    {{-- ============================================================
         INTERACCIONES DEL LOGIN
    ============================================================= --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const emailInput =
                document.getElementById('email');

            const passwordInput =
                document.getElementById('password');

            const togglePassword =
                document.getElementById('togglePassword');

            const togglePasswordIcon =
                document.getElementById('togglePasswordIcon');


            /*
             * Mantener los campos visualmente vacíos al cargar.
             * Algunos navegadores aplican autofill después de renderizar.
             */
            const limpiarCampos = function () {
                if (emailInput) {
                    emailInput.value = '';
                }

                if (passwordInput) {
                    passwordInput.value = '';
                    passwordInput.type = 'password';
                }
            };

            limpiarCampos();

            setTimeout(limpiarCampos, 100);
            setTimeout(limpiarCampos, 400);


            /*
             * Mostrar / ocultar contraseña.
             */
            if (
                !passwordInput ||
                !togglePassword ||
                !togglePasswordIcon
            ) {
                return;
            }

            togglePassword.addEventListener('click', function () {
                const mostrarPassword =
                    passwordInput.type === 'password';

                passwordInput.type =
                    mostrarPassword
                        ? 'text'
                        : 'password';

                togglePasswordIcon.classList.toggle(
                    'bi-eye-fill',
                    !mostrarPassword
                );

                togglePasswordIcon.classList.toggle(
                    'bi-eye-slash-fill',
                    mostrarPassword
                );

                const textoAccesible =
                    mostrarPassword
                        ? 'Ocultar contraseña'
                        : 'Mostrar contraseña';

                togglePassword.setAttribute(
                    'aria-label',
                    textoAccesible
                );

                togglePassword.setAttribute(
                    'title',
                    textoAccesible
                );

                togglePassword.setAttribute(
                    'aria-pressed',
                    mostrarPassword
                        ? 'true'
                        : 'false'
                );
            });
        });
    </script>

</body>

</html>