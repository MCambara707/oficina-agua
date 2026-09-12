<nav class="app-header {{ config('adminlte.classes_topnav', 'navbar-expand bg-body') }} navbar" aria-label="Barra de herramientas">
    <div class="{{ config('adminlte.classes_topnav_container', 'container-fluid') }}">
        <ul class="navbar-nav align-items-center">
            <li class="nav-item">
                <button type="button" class="nav-link" data-lte-toggle="sidebar" aria-label="Abrir o cerrar menú lateral">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
            </li>
            
        </ul>

        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item">
                <button type="button" class="nav-link" data-lte-toggle="fullscreen" aria-label="Pantalla completa">
                    <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen" aria-hidden="true"></i>
                    <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none" aria-hidden="true"></i>
                </button>
            </li>

            @if (config('adminlte.color_mode_toggle', true))
                <li class="nav-item dropdown">
                    <button type="button" class="nav-link" id="bd-theme" aria-label="Cambiar tema de color"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sun-fill" data-lte-theme-icon="light" aria-hidden="true"></i>
                        <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark" aria-hidden="true"></i>
                        <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme">
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light" aria-pressed="false">
                                <i class="bi bi-sun-fill me-2" aria-hidden="true"></i>Claro
                                <i class="bi bi-check-lg ms-auto d-none" aria-hidden="true"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                                <i class="bi bi-moon-fill me-2" aria-hidden="true"></i>Oscuro
                                <i class="bi bi-check-lg ms-auto d-none" aria-hidden="true"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto" aria-pressed="true">
                                <i class="bi bi-circle-half me-2" aria-hidden="true"></i>Automático
                                <i class="bi bi-check-lg ms-auto d-none" aria-hidden="true"></i>
                            </button>
                        </li>
                    </ul>
                </li>
            @endif

            @if (config('adminlte.usermenu_enabled', true))
                @include('adminlte::partials.usermenu')
            @endif
        </ul>
    </div>
</nav>
