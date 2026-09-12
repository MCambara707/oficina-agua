@php
    $items = app('adminlte')->menu('sidebar');
    $sidebarTheme = config('adminlte.sidebar_theme', 'dark');
    $sidebarClasses = config('adminlte.classes_sidebar', 'bg-body-secondary shadow');
@endphp

<aside
    class="app-sidebar {{ $sidebarClasses }}"
    @if ($sidebarTheme === 'dark') data-bs-theme="dark" @endif
>
    <div class="sidebar-brand {{ config('adminlte.classes_brand') }}">
        <a
            href="{{ route('inicio') }}"
            class="brand-link aquatech-brand-link"
            aria-label="AquaTech GT — Oficina del Agua"
        >
            <img
                src="{{ asset('img/branding/Logo_AquatechGt_icono.png') }}"
                alt="AquaTech GT"
                class="aquatech-sidebar-logo-icon"
                width="512"
                height="512"
            >

            <img
                src="{{ asset('img/branding/Logo_AquatechGt.png') }}"
                alt="AquaTech GT"
                class="aquatech-sidebar-logo-full"
                width="1933"
                height="506"
            >
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2" aria-label="Navegación principal">
            <ul
                class="nav sidebar-menu flex-column {{ config('adminlte.classes_sidebar_nav') }}"
                data-lte-toggle="treeview"
                data-accordion="false"
                role="menu"
                id="navigation"
            >
                @foreach ($items as $item)
                    @include('adminlte::partials.menu-item', ['item' => $item])
                @endforeach
            </ul>
        </nav>
    </div>
</aside>