@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <h1>Mantenimiento de Usuarios</h1>
@stop

@section('content')

    @if (session('exito'))
        <div class="alert alert-success">
            {{ session('exito') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">

        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <form
                    method="GET"
                    action="{{ route('usuarios.index') }}"
                    class="d-flex flex-column flex-sm-row gap-2 col-12 col-lg-7"
                >
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        value="{{ $busqueda }}"
                        placeholder="Buscar por nombre, correo o rol"
                    >

                    <button
                        type="submit"
                        class="btn btn-secondary text-nowrap"
                    >
                        Buscar
                    </button>
                </form>

                <a
                    href="{{ route('usuarios.create') }}"
                    class="btn btn-primary"
                >
                    + Nuevo Usuario
                </a>

            </div>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">

                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo electrónico</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th style="width: 230px;">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($usuarios as $usuario)

                            <tr>

                                <td>
                                    {{ $usuario->nombre }}

                                    @if (auth()->id() === $usuario->id)
                                        <span class="badge text-bg-primary ms-1">
                                            Sesión actual
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $usuario->email }}
                                </td>

                                <td>
                                    {{ $usuario->rol?->nombre ?? 'Sin rol' }}
                                </td>

                                <td>
                                    @if ($usuario->activo)
                                        <span class="badge text-bg-success">
                                            Activo
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary">
                                            Inactivo
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a
                                            href="{{ route('usuarios.edit', $usuario) }}"
                                            class="btn btn-sm btn-warning"
                                        >
                                            Editar
                                        </a>

                                        @if (auth()->id() === $usuario->id && $usuario->activo)

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-secondary"
                                                disabled
                                                title="No puede desactivar su propia cuenta"
                                            >
                                                Desactivar
                                            </button>

                                        @else

                                            <form
                                                action="{{ route('usuarios.cambiar-estado', $usuario) }}"
                                                method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm(
                                                    '{{ $usuario->activo
                                                        ? '¿Seguro que desea desactivar este usuario?'
                                                        : '¿Seguro que desea activar este usuario?' }}'
                                                );"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                @if ($usuario->activo)
                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-danger"
                                                    >
                                                        Desactivar
                                                    </button>
                                                @else
                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-success"
                                                    >
                                                        Activar
                                                    </button>
                                                @endif

                                            </form>

                                        @endif
                                    </div>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="5"
                                    class="text-center py-4"
                                >
                                    No hay usuarios registrados.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>

        </div>

        <div class="card-footer">
            {{ $usuarios->links('pagination::bootstrap-5') }}
        </div>

    </div>

@stop
