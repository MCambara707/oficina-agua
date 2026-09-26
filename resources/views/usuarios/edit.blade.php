@extends('adminlte::page')

@section('title', 'Editar Usuario')

@section('content_header')
    <h1>Editar Usuario</h1>
@stop

@section('content')

    <div class="card">
        <div class="card-body">

            @php($erroresGenerales = collect($errors->getMessages())->except(['email'])->flatten()->all())
            @if (count($erroresGenerales))
                <div class="alert alert-danger">
                    <ul class="mb-0">

                        @foreach ($erroresGenerales as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach

                    </ul>
                </div>
            @endif

            <form
                action="{{ route('usuarios.update', $usuario) }}"
                method="POST"
                data-validacion-campos
                data-validacion-correo-url="{{ route('usuarios.validar-correo') }}"
            >

                @csrf
                @method('PUT')

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
                        value="{{ old('nombre', $usuario->nombre) }}"
                        required
                        autocomplete="name"
                    >

                </div>

                <div class="form-group mb-3">

                    <label for="email">
                        Correo electrónico *
                    </label>

                    <input
                        type="text"
                        inputmode="email"
                        name="email" data-validacion="email" data-validacion-max="150"
                        aria-describedby="email-error email-feedback"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        id="email"
                        class="form-control @error('email') is-invalid @enderror"
                        maxlength="150"
                        value="{{ old('email', $usuario->email) }}"
                        required
                        autocomplete="email"
                    >
                    @include('partials.validacion-campo', ['campo' => 'email'])

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
                                {{ (string) old(
                                    'rol_id',
                                    $usuario->rol_id
                                ) === (string) $rol->id
                                    ? 'selected'
                                    : '' }}
                            >
                                {{ $rol->nombre }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <hr>

                <div class="alert alert-info">
                    Deje los campos de contraseña vacíos si no desea cambiar
                    la contraseña actual.
                </div>

                <div class="form-group mb-3">

                    <label for="password">
                        Nueva contraseña
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        minlength="8"
                        autocomplete="new-password"
                    >

                    <small class="form-text text-muted">
                        Opcional. Mínimo 8 caracteres.
                    </small>

                </div>

                <div class="form-group mb-3">

                    <label for="password_confirmation">
                        Confirmar nueva contraseña
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        class="form-control"
                        minlength="8"
                        autocomplete="new-password"
                    >

                </div>

                <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Actualizar
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