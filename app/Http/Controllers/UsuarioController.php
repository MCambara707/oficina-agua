<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    private const ROLES_PERMITIDOS = [
        'Administrador',
        'Secretaria',
        'Lector',
    ];

    /**
     * Listado de usuarios.
     */
    public function index(Request $request)
    {
        $busqueda = trim((string) $request->input('q', ''));

        $usuarios = User::with('rol')
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($subquery) use ($busqueda) {
                    $subquery
                        ->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('email', 'like', "%{$busqueda}%")
                        ->orWhereHas('rol', function ($queryRol) use ($busqueda) {
                            $queryRol->where(
                                'nombre',
                                'like',
                                "%{$busqueda}%"
                            );
                        });
                });
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view(
            'usuarios.index',
            compact('usuarios', 'busqueda')
        );
    }

    /**
     * Formulario para crear un usuario.
     */
    public function create()
    {
        $roles = $this->obtenerRolesDisponibles();

        return view(
            'usuarios.create',
            compact('roles')
        );
    }

    /**
     * Registrar un usuario nuevo.
     */
    public function store(Request $request)
    {
        $datos = $this->validarUsuario($request);

        /*
         * AQ-67:
         * Nunca guardar contraseñas en texto plano.
         */
        $datos['password'] = Hash::make(
            $datos['password']
        );

        /*
         * Todo usuario nuevo inicia activo.
         */
        $datos['activo'] = 1;

        User::create($datos);

        return redirect()
            ->route('usuarios.index')
            ->with(
                'exito',
                'Usuario creado correctamente.'
            );
    }

    /**
     * Formulario para editar un usuario.
     */
    public function edit(User $usuario)
    {
        $roles = $this->obtenerRolesDisponibles();

        return view(
            'usuarios.edit',
            compact('usuario', 'roles')
        );
    }

    /**
     * Actualizar información del usuario.
     */
    public function update(
    Request $request,
    User $usuario
) {
    $datos = $this->validarUsuario(
        $request,
        $usuario
    );

    /*
     * Evita que el Administrador cambie su propio rol
     * y pierda acceso al mantenimiento de usuarios.
     */
    if (
        Auth::id() === $usuario->id
        && (int) $datos['rol_id'] !== (int) $usuario->rol_id
    ) {
        return back()
            ->withInput()
            ->withErrors([
                'rol_id' => 'No puede cambiar el rol de su propio usuario mientras tiene la sesión iniciada.',
            ]);
    }
/*
 * Evita quitarle el rol al último Administrador activo.
 */
if (
    $usuario->rol?->nombre === 'Administrador'
    && (int) $datos['rol_id'] !== (int) $usuario->rol_id
    && $usuario->activo
) {
    $administradoresActivos = User::whereHas('rol', function ($query) {
            $query->where('nombre', 'Administrador');
        })
        ->where('activo', true)
        ->count();

    if ($administradoresActivos <= 1) {
        return back()
            ->withInput()
            ->withErrors([
                'rol_id' => 'No se puede cambiar el rol del último Administrador activo del sistema.',
            ]);
    }
}
    /*
     * Si no se escribe una nueva contraseña,
     * se conserva la contraseña existente.
     */
    if ($request->filled('password')) {
        $datos['password'] = Hash::make(
            $datos['password']
        );
    } else {
        unset($datos['password']);
    }

    $usuario->update($datos);

    return redirect()
        ->route('usuarios.index')
        ->with(
            'exito',
            'Usuario actualizado correctamente.'
        );
}

    /**
     * Activar o desactivar un usuario.
     *
     * AQ-67 exige baja lógica:
     * NO se utiliza DELETE.
     */
    public function cambiarEstado(User $usuario)
    {
        /*
         * Evita que el administrador que está usando
         * actualmente el sistema se desactive a sí mismo.
         */
        if (
            Auth::id() === $usuario->id
            && $usuario->activo
        ) {
            return redirect()
                ->route('usuarios.index')
                ->with(
                    'error',
                    'No puede desactivar su propio usuario mientras tiene la sesión iniciada.'
                );
        }

        if (
    $usuario->rol?->nombre === 'Administrador'
    && $usuario->activo
) {
    $administradoresActivos = User::whereHas('rol', function ($query) {
            $query->where('nombre', 'Administrador');
        })
        ->where('activo', true)
        ->count();

    if ($administradoresActivos <= 1) {
        return redirect()
            ->route('usuarios.index')
            ->with(
                'error',
                'No se puede desactivar al último Administrador activo del sistema.'
            );
    }
}

        $nuevoEstado = !$usuario->activo;

        $usuario->update([
            'activo' => $nuevoEstado,
        ]);

        $mensaje = $nuevoEstado
            ? 'Usuario activado correctamente.'
            : 'Usuario desactivado correctamente.';

        return redirect()
            ->route('usuarios.index')
            ->with('exito', $mensaje);
    }

    /**
     * Validaciones utilizadas al crear y editar.
     */
    private function validarUsuario(
        Request $request,
        ?User $usuario = null
    ): array {
        $reglaEmail = Rule::unique(
            'users',
            'email'
        );

        if ($usuario) {
            $reglaEmail->ignore($usuario->id);
        }

        $reglasPassword = $usuario
            ? [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ]
            : [
                'required',
                'string',
                'min:8',
                'confirmed',
            ];

        return $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:120',
                ],

                'email' => [
                    'required',
                    'email',
                    'max:150',
                    $reglaEmail,
                ],

                'rol_id' => [
                    'required',

                    Rule::exists(
                        'roles',
                        'id'
                    )->where(function ($query) {
                        $query
                            ->where('activo', 1)
                            ->whereIn(
                                'nombre',
                                self::ROLES_PERMITIDOS
                            );
                    }),
                ],

                'password' => $reglasPassword,
            ],
            [
                'nombre.required' =>
                    'El nombre del usuario es obligatorio.',

                'nombre.max' =>
                    'El nombre no puede superar los 120 caracteres.',

                'email.required' =>
                    'El correo electrónico es obligatorio.',

                'email.email' =>
                    'Ingrese un correo electrónico válido.',

                'email.unique' =>
                    'Ya existe un usuario registrado con ese correo electrónico.',

                'rol_id.required' =>
                    'Debe seleccionar un rol.',

                'rol_id.exists' =>
                    'El rol seleccionado no es válido o se encuentra inactivo.',

                'password.required' =>
                    'La contraseña es obligatoria.',

                'password.min' =>
                    'La contraseña debe contener al menos 8 caracteres.',

                'password.confirmed' =>
                    'La confirmación de la contraseña no coincide.',
            ]
        );
    }

    /**
     * Obtiene únicamente los roles permitidos y activos.
     */
    private function obtenerRolesDisponibles()
    {
        return Role::where('activo', true)
            ->whereIn(
                'nombre',
                self::ROLES_PERMITIDOS
            )
            ->orderBy('id')
            ->get();
    }
}