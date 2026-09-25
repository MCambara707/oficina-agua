@extends('adminlte::page')

@section('title', 'Quiénes somos')

@section('content_header')
    <div>
        <h1 class="mb-1">Quiénes somos</h1>
        <p class="text-muted mb-0">
            Administra la información institucional publicada en AquaTech GT.
        </p>
    </div>
@stop

@section('content')

    @if (session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>No fue posible guardar la información.</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        action="{{ route('landing.quienes-somos.update') }}"
        method="POST"
    >
        @csrf
        @method('PUT')

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-buildings me-1"></i>
                    Información institucional
                </h3>
            </div>

            <div class="card-body">

                <div class="mb-3">
                    <label
                        for="historia"
                        class="form-label"
                    >
                        Historia
                    </label>

                    <textarea
                        name="historia"
                        id="historia"
                        class="form-control"
                        rows="6"
                        maxlength="5000"
                    >{{ old(
                        'historia',
                        $configuracion?->historia
                    ) }}</textarea>
                </div>

                <div class="row">

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label
                                for="mision"
                                class="form-label"
                            >
                                Misión
                            </label>

                            <textarea
                                name="mision"
                                id="mision"
                                class="form-control"
                                rows="6"
                                maxlength="5000"
                            >{{ old(
                                'mision',
                                $configuracion?->mision
                            ) }}</textarea>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label
                                for="vision"
                                class="form-label"
                            >
                                Visión
                            </label>

                            <textarea
                                name="vision"
                                id="vision"
                                class="form-control"
                                rows="6"
                                maxlength="5000"
                            >{{ old(
                                'vision',
                                $configuracion?->vision
                            ) }}</textarea>
                        </div>
                    </div>

                </div>

                <div class="mb-4">
                    <label
                        for="diferenciadores"
                        class="form-label"
                    >
                        Qué nos diferencia
                    </label>

                    <textarea
                        name="diferenciadores"
                        id="diferenciadores"
                        class="form-control"
                        rows="5"
                        maxlength="5000"
                    >{{ old(
                        'diferenciadores',
                        $configuracion?->diferenciadores
                    ) }}</textarea>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="bi bi-save me-1"></i>
                    Guardar cambios
                </button>

            </div>
        </div>

    </form>

@stop