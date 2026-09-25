@extends('adminlte::page')

@section('title', 'Preguntas frecuentes')

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h1 class="mb-1">Preguntas frecuentes</h1>
            <p class="text-muted mb-0">
                Administra las preguntas y respuestas mostradas en AquaTech GT.
            </p>
        </div>

        <a
            href="{{ route('landing.preguntas.create') }}"
            class="btn btn-primary"
        >
            <i class="bi bi-plus-circle me-1"></i>
            Nueva pregunta
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
                        <th>Pregunta</th>
                        <th>Respuesta</th>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th style="width: 190px;">Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($preguntas as $pregunta)

                        <tr>

                            <td>
                                <strong>
                                    {{ $pregunta->pregunta }}
                                </strong>
                            </td>

                            <td>
                                {{ \Illuminate\Support\Str::limit(
                                    $pregunta->respuesta,
                                    120
                                ) }}
                            </td>

                            <td>
                                {{ $pregunta->orden }}
                            </td>

                            <td>
                                @if ($pregunta->activo)
                                    <span class="badge text-bg-success">
                                        Activa
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">
                                        Inactiva
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex flex-nowrap gap-2">

                                    <a
                                        href="{{ route(
                                            'landing.preguntas.edit',
                                            $pregunta
                                        ) }}"
                                        class="btn btn-sm btn-warning"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        action="{{ route(
                                            'landing.preguntas.destroy',
                                            $pregunta
                                        ) }}"
                                        method="POST"
                                        onsubmit="return confirm('¿Seguro que deseas eliminar esta pregunta?');"
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
                                No hay preguntas frecuentes registradas todavía.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($preguntas->hasPages())
            <div class="card-footer">
                {{ $preguntas->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </div>

@stop