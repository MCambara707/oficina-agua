@extends('adminlte::page')

@section('title', 'Avisos públicos')

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h1 class="mb-1">Avisos públicos</h1>
            <p class="text-muted mb-0">
                Administra los avisos que se muestran en la landing de AquaTech GT.
            </p>
        </div>

        <a href="{{ route('landing.avisos.create') }}"
           class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Nuevo aviso
        </a>
    </div>
@stop

@section('content')

    @if (session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">

        <div class="card-body p-0 table-responsive">

            <table class="table table-striped mb-0 align-middle">

                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Vigencia</th>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th style="width: 190px;">Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($avisos as $aviso)

                        <tr>

                            <td>
                                <strong>{{ $aviso->titulo }}</strong>

                                <div class="text-muted small mt-1">
                                    {{ \Illuminate\Support\Str::limit($aviso->contenido, 100) }}
                                </div>
                            </td>

                            <td>
                                <div>
                                    Inicio:
                                    {{ $aviso->fecha_inicio?->format('d/m/Y') ?? 'Sin límite' }}
                                </div>

                                <div>
                                    Fin:
                                    {{ $aviso->fecha_fin?->format('d/m/Y') ?? 'Sin límite' }}
                                </div>
                            </td>

                            <td>
                                {{ $aviso->orden }}
                            </td>

                            <td>
                                @if ($aviso->activo)
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
                                <div class="d-flex flex-nowrap gap-2">

                                    <a
                                        href="{{ route('landing.avisos.edit', $aviso) }}"
                                        class="btn btn-sm btn-warning"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        action="{{ route('landing.avisos.destroy', $aviso) }}"
                                        method="POST"
                                        onsubmit="return confirm('¿Seguro que deseas eliminar este aviso?');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                        >
                                            Eliminar
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="text-center py-4">
                                No hay avisos registrados todavía.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($avisos->hasPages())
            <div class="card-footer">
                {{ $avisos->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </div>

@stop