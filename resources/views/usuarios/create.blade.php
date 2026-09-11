@extends('adminlte::page')

@section('title', 'Nuevo Usuario')

@section('content_header')
    <h1>Nuevo Usuario</h1>
@stop

@section('content')

    <div class="card">
        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">

                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach

                    </ul>
                </div>
            @endif

            <form
                action="{{ route('usuarios.store') }}"
                method="POST"
            >

                @csrf

                <div class="form-group mb-3">

                    <label for="nombre">
                        Nombre *
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        class="form-control"
                        maxlength="120"
                        value="{{ old('nombre') }}"
                        required
                        autocomplete="name"
                    >

                </div>

                <div class="form-group mb-3">

                    <label for="email">
                        Correo electrónico *
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control"
                        maxlength="150"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                    >

                    <small class="form-text text-muted">
                        El correo debe ser único dentro del sistema.
                    </small>

                </div>

                <div class="form-group mb-3">

                    <label for="rol_id">
                        Rol *
                    </label>

                    <select
                        name="rol_id"
                        id="rol_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Seleccione un rol
                        </option>

                        @foreach ($roles as $rol)

                            <option
                                value="{{ $rol->id }}"
                                {{ (string) old('rol_id') === (string) $rol->id
                                    ? 'selected'
                                    : '' }}
                            >
                                {{ $rol->nombre }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="form-group mb-3">

                    <label for="password">
                        Contraseña *
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        minlength="8"
                        required
                        autocomplete="new-password"
                    >

                    <small class="form-text text-muted">
                        Debe contener al menos 8 caracteres.
                    </small>

                </div>

                <div class="form-group mb-3">

                    <label for="password_confirmation">
                        Confirmar contraseña *
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        class="form-control"
                        minlength="8"
                        required
                        autocomplete="new-password"
                    >

                </div>

                <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Guardar
                    </button>

                    <a
                        href="{{ route('usuarios.index') }}"
                        class="btn btn-secondary"
                    >
                        Cancelar
                    </a>
                </div>
            </form>

        </div>
    </div>

@stop