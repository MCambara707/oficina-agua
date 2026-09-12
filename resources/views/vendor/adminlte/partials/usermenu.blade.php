@php($usuario = auth()->user())
<li class="nav-item dropdown user-menu">
    <button type="button" class="nav-link dropdown-toggle gap-2" id="menu-usuario"
            data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menú de usuario">
        <i class="bi bi-person-circle fs-4" aria-hidden="true"></i>
        <span class="d-none d-md-inline">{{ $usuario?->nombre ?? $usuario?->email }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end aquatech-user-menu" aria-labelledby="menu-usuario">
        <li class="px-3 py-2 text-break">
            <div class="fw-semibold">{{ $usuario?->nombre ?? $usuario?->email }}</div>
            <small class="text-body-secondary">{{ $usuario?->rol?->nombre }}</small>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    Cerrar sesión
                </button>
            </form>
        </li>
    </ul>
</li>
