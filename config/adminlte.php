<?php

use ColorlibHQ\AdminLte\Menu\Filters\ActiveFilter;
use ColorlibHQ\AdminLte\Menu\Filters\GateFilter;
use ColorlibHQ\AdminLte\Menu\Filters\HrefFilter;

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    */

    'title' => 'Sistema de Gestión — Oficina del Agua',
    'title_prefix' => '',
    'title_postfix' => '',


    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,


    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    */

    'google_fonts' => [
        'allowed' => true,
    ],


    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    */

    'logo' => 'AquaTech',

    'logo_img' =>
        'img/branding/Logo_AquatechGt.png',

    'logo_img_class' =>
        '',

    'logo_img_alt' =>
        'AquaTech GT',


    /*
    |--------------------------------------------------------------------------
    | Authentication logo
    |--------------------------------------------------------------------------
    */

    'auth_logo' => [

        'enabled' => false,

        'img' => [

            'path' =>
                'img/branding/Logo_AquatechGt.png',

            'alt' =>
                'AquaTech GT',

            'class' =>
                '',

            'width' =>
                50,

            'height' =>
                50,
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | User menu
    |--------------------------------------------------------------------------
    */

    'usermenu_enabled' => true,

    'usermenu_header' => false,

    'usermenu_header_class' =>
        'text-bg-primary',

    'usermenu_image' => false,

    'usermenu_desc' => false,

    'usermenu_profile_url' => false,


    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    */

    'layout_topnav' => null,

    'layout_boxed' => null,

    'layout_fixed_sidebar' => true,

    'layout_fixed_navbar' => true,

    'layout_fixed_footer' => null,

    'layout_dark_mode' => null,

    'layout_rtl' => false,


    /*
    |--------------------------------------------------------------------------
    | Footer & Preloader
    |--------------------------------------------------------------------------
    */

    'footer_left' =>
        'Copyright &copy; 2026 '
        . '<a href="/equipo" class="text-decoration-none">'
        . 'AquaTech — Grupo 5'
        . '</a>. Todos los derechos reservados.',

    'footer_right' =>
        'Sistema de Gestión — Oficina del Agua',

    'preloader' => false,

    'control_sidebar' => false,

    'control_sidebar_theme' =>
        'dark',


    /*
    |--------------------------------------------------------------------------
    | Documentation / Demo
    |--------------------------------------------------------------------------
    */

    'sidebar_docs_url' =>
        null,

    'demo' => false,

    'demo_middleware' => [
        'web',
        'auth',
    ],

    'docs' => false,

    'docs_middleware' => [
        'web',
    ],


    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    */

    'sidebar_breakpoint' =>
        'lg',

    'sidebar_mini' =>
        true,

    'sidebar_collapse' =>
        false,

    'sidebar_collapse_auto_size' =>
        false,

    /*
     * El sidebar utiliza fondo claro.
     * El scrollbar oscuro mantiene un contraste adecuado.
     */
    'sidebar_scrollbar_theme' =>
        'os-theme-dark',

    'sidebar_scrollbar_auto_hide' =>
        'leave',


    /*
    |--------------------------------------------------------------------------
    | Color theme
    |--------------------------------------------------------------------------
    */

    /*
     * AQ-43:
     * El menú lateral utiliza la identidad visual AquaTech.
     */
    'sidebar_theme' =>
        'light',

    'primary_color' =>
        null,

    'sidebar_color' =>
        null,

    'navbar_color' =>
        null,

    'footer_color' =>
        null,


    /*
    |--------------------------------------------------------------------------
    | Custom body / element classes
    |--------------------------------------------------------------------------
    */

    'classes_body' =>
        '',

    'classes_brand' =>
        '',

    'classes_brand_text' =>
        '',

    'classes_content_wrapper' =>
        '',

    'classes_content_header' =>
        '',

    'classes_content' =>
        '',

    /*
     * Clase personalizada del sidebar AquaTech.
     */
    'classes_sidebar' =>
        'aquatech-sidebar shadow-sm',

    'classes_sidebar_nav' =>
        '',

    'classes_topnav' =>
        'navbar-expand bg-body',

    'classes_topnav_nav' =>
        'navbar',

    'classes_topnav_container' =>
        'container-fluid',


    /*
    |--------------------------------------------------------------------------
    | Color mode toggle
    |--------------------------------------------------------------------------
    */

    'color_mode_toggle' => true,


    /*
    |--------------------------------------------------------------------------
    | Menu
    |--------------------------------------------------------------------------
    |
    | El menú se organiza según el flujo natural del sistema:
    |
    | 1. Gestión:
    |    creación y administración de registros base.
    |
    | 2. Operación:
    |    lectura, consulta y seguimiento de cuentas.
    |
    | 3. Cobros:
    |    registro de pagos.
    |
    */

    'menu' => [

        /*
        |--------------------------------------------------------------------------
        | GESTIÓN
        |--------------------------------------------------------------------------
        */

        [
            'header' =>
                'GESTIÓN',

            'can' =>
                'ver-modulos-administrativos',
        ],


        [
            'text' =>
                'Alta de servicio',

            'route' =>
                'alta-servicio.create',

            'icon' =>
                'bi bi-person-plus',

            'active' => [
                'alta-servicio*',
            ],

            'can' =>
                'ver-modulos-administrativos',
        ],


        [
            'text' =>
                'Clientes',

            'route' =>
                'clientes.index',

            'icon' =>
                'bi bi-people',

            'active' => [
                'clientes*',
            ],

            'can' =>
                'ver-modulos-administrativos',
        ],


        [
            'text' =>
                'Contadores',

            'route' =>
                'contadores.index',

            'icon' =>
                'bi bi-speedometer2',

            'active' => [
                'contadores*',
            ],

            'can' =>
                'ver-modulos-administrativos',
        ],


        [
            'text' =>
                'Servicios',

            'route' =>
                'servicios.index',

            'icon' =>
                'bi bi-tools',

            'active' => [
                'servicios*',
            ],

            'can' =>
                'ver-modulos-administrativos',
        ],


        [
            'text' =>
                'Tarifas',

            'route' =>
                'tarifas.index',

            'icon' =>
                'bi bi-cash-coin',

            'active' => [
                'tarifas*',
            ],

            'can' =>
                'ver-modulos-administrativos',
        ],


        [
            'text' =>
                'Usuarios',

            'route' =>
                'usuarios.index',

            'icon' =>
                'bi bi-person-gear',

            'active' => [
                'usuarios*',
            ],

            'can' =>
                'administrar-usuarios',
        ],


        /*
        |--------------------------------------------------------------------------
        | OPERACIÓN
        |--------------------------------------------------------------------------
        */

        [
            'header' =>
                'OPERACIÓN',
        ],


        [
            'text' =>
                'Lecturas',

            'route' =>
                'lecturas.index',

            'icon' =>
                'bi bi-water',

            'active' => [
                'lecturas*',
            ],
        ],


        [
            'text' =>
                'Estado de cuenta',

            'route' =>
                'dashboard.estado-cuenta',

            'icon' =>
                'bi bi-clipboard-data',

            'active' => [
                'dashboard/estado-cuenta*',
            ],

            'can' =>
                'ver-modulos-administrativos',
        ],


        [
            'text' =>
                'Historial de recibos',

            'route' =>
                'recibos.index',

            'icon' =>
                'bi bi-receipt',

            'active' => [
                'recibos*',
            ],

            'can' =>
                'ver-modulos-administrativos',
        ],


        /*
        |--------------------------------------------------------------------------
        | COBROS
        |--------------------------------------------------------------------------
        */

        [
            'header' =>
                'COBROS',

            'can' =>
                'ver-modulos-administrativos',
        ],


        [
            'text' =>
                'Pagos',

            'route' =>
                'pagos.index',

            'icon' =>
                'bi bi-credit-card',

            'active' => [
                'pagos*',
            ],

            'can' =>
                'ver-modulos-administrativos',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Plugins
    |--------------------------------------------------------------------------
    */

    'plugins' => [

        'flatpickr' => [

            'enabled' =>
                false,

            'css' =>
                'vendor/flatpickr/flatpickr.min.css',

            'js' =>
                'vendor/flatpickr/flatpickr.min.js',
        ],


        'tom_select' => [

            'enabled' =>
                false,

            'css' =>
                'vendor/tom-select/tom-select.bootstrap5.min.css',

            'js' =>
                'vendor/tom-select/tom-select.complete.min.js',
        ],


        'tabulator' => [

            'enabled' =>
                false,

            'css' =>
                'vendor/tabulator-tables/tabulator.min.css',

            'js' =>
                'vendor/tabulator-tables/tabulator.min.js',
        ],


        'quill' => [

            'enabled' =>
                false,

            'css' =>
                'vendor/quill/quill.snow.css',

            'js' =>
                'vendor/quill/quill.min.js',
        ],


        'apexcharts' => [

            'enabled' =>
                false,

            'js' =>
                'vendor/apexcharts/apexcharts.min.js',
        ],


        'jsvectormap' => [

            'enabled' =>
                false,

            'css' =>
                'vendor/jsvectormap/jsvectormap.min.css',

            'js' => [

                'vendor/jsvectormap/jsvectormap.min.js',

                'vendor/jsvectormap/maps/world.js',
            ],
        ],


        'fullcalendar' => [

            'enabled' =>
                false,

            'css' =>
                'vendor/fullcalendar/index.global.min.css',

            'js' =>
                'vendor/fullcalendar/index.global.min.js',
        ],


        'sortablejs' => [

            'enabled' =>
                false,

            'js' =>
                'vendor/sortablejs/sortablejs.min.js',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Estos filtros permiten:
    |
    | - resolver rutas y URLs;
    | - marcar la opción activa;
    | - aplicar permisos mediante "can".
    |
    */

    'filters' => [

        HrefFilter::class,

        ActiveFilter::class,

        GateFilter::class,
    ],
];