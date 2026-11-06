<?php

namespace App\Http\Controllers;

use App\Mail\UsuarioAccesoMail;
use App\Models\Estado;
use App\Models\Genero;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class UsuarioController extends Controller
{
    private const HIDDEN_COMMUNITY_ROLES = ['Administrador', 'Master', 'Invitado'];

    public function index(): View
    {
        return view('usuarios.index', [
            'estados' => Estado::query()
                ->whereHas('usuarios')
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'roles' => Rol::query()
                ->where('nombre', '!=', 'Master')
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identificacion' => ['nullable', 'string', 'max:64'],
            'estado_id' => ['nullable', 'integer', 'exists:estados,id'],
            'rol_id' => [
                'nullable',
                'integer',
                Rule::exists('rols', 'id')->where(
                    fn (Builder $query) => $query->where('nombre', '!=', 'Master')
                ),
            ],
            'cursor' => ['nullable', 'string', 'max:2048'],
        ]);

        $identificacion = trim((string) ($validated['identificacion'] ?? ''));
        $usuarios = Usuario::query()
            ->whereHas('rol', fn (EloquentBuilder $query) => $query->where('nombre', '!=', 'Master'))
            ->with(['estado:id,nombre', 'rol:id,nombre'])
            ->when($identificacion !== '', function (EloquentBuilder $query) use ($identificacion): void {
                $query->where(function (EloquentBuilder $query) use ($identificacion): void {
                    $query->where('cedula', 'like', "%{$identificacion}%")
                        ->orWhere('codigo', 'like', "%{$identificacion}%");
                });
            })
            ->when(isset($validated['estado_id']), function (EloquentBuilder $query) use ($validated): void {
                $query->where('estado_id', $validated['estado_id']);
            })
            ->when(isset($validated['rol_id']), function (EloquentBuilder $query) use ($validated): void {
                $query->where('rol_id', $validated['rol_id']);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(config('app.usuarios_cursor_size', 20));

        return response()->json([
            'data' => $usuarios->getCollection()
                ->map(fn (Usuario $usuario): array => [
                    'id' => $usuario->id,
                    'nombres' => $usuario->nombres,
                    'apellidos' => $usuario->apellidos,
                    'cedula' => $usuario->cedula,
                    'estado' => $usuario->estado?->nombre,
                    'rol' => $usuario->rol?->nombre,
                    'edit_url' => url('/usuarios/'.$usuario->id.'/edit'),
                ])
                ->values(),
            'next_cursor' => $usuarios->nextCursor()?->encode(),
            'has_more' => $usuarios->hasMorePages(),
        ]);
    }

    public function detail(Usuario $usuario): JsonResponse
    {
        $usuario->load([
            'estado:id,nombre',
            'genero:id,nombre',
            'rol:id,nombre',
            'sesion:id,usuario_id,email',
        ]);

        $isGuest = $usuario->rol?->nombre === 'Invitado';
        $email = $usuario->sesion?->email;
        $codigo = $usuario->codigo;

        if ($isGuest || ! filled($email)) {
            $email = null;
        }

        if (! filled($codigo) || str_starts_with($codigo, 'INV-') || str_starts_with($codigo, 'USR-')) {
            $codigo = null;
        }

        return response()->json([
            'data' => [
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'cedula' => $usuario->cedula,
                'codigo' => $codigo,
                'edad' => $usuario->year_nace > 0 ? now()->year - $usuario->year_nace : null,
                'email' => $email,
                'genero' => $usuario->genero?->nombre,
                'estado' => $usuario->estado?->nombre,
                'rol' => $usuario->rol?->nombre,
                'edit_url' => url('/usuarios/'.$usuario->id.'/edit'),
            ],
        ]);
    }

    public function edit(Usuario $usuario): View
    {
        $usuario->load(['rol:id,nombre', 'sesion:id,usuario_id,email']);
        $isMaster = auth()->user()?->usuario?->rol?->nombre === 'Master';

        $roles = Rol::query()
            ->where('nombre', '!=', 'Invitado')
            ->when(! $isMaster, function (EloquentBuilder $query) use ($usuario): void {
                $query->where(function (EloquentBuilder $query) use ($usuario): void {
                    $query->whereNotIn('nombre', self::HIDDEN_COMMUNITY_ROLES)
                        ->when($usuario->rol?->nombre !== 'Invitado', function (EloquentBuilder $query) use ($usuario): void {
                            $query->orWhereKey($usuario->rol_id);
                        });
                });
            })
            ->orderByRaw("CASE WHEN nombre = 'Estudiante' THEN 0 ELSE 1 END")
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('usuarios.edit', [
            'usuario' => $usuario,
            'estados' => Estado::query()->orderBy('nombre')->get(['id', 'nombre']),
            'generos' => Genero::query()->orderBy('nombre')->get(['id', 'nombre']),
            'roles' => $roles,
            'isGuest' => $usuario->rol?->nombre === 'Invitado',
            'codigoEditable' => ! str_starts_with((string) $usuario->codigo, 'INV-')
                && ! str_starts_with((string) $usuario->codigo, 'USR-'),
        ]);
    }

    public function update(Request $request, Usuario $usuario): RedirectResponse
    {
        $usuario->loadMissing(['rol', 'sesion']);
        $isMaster = $request->user()?->usuario?->rol?->nombre === 'Master';
        $isCommunity = $usuario->rol?->nombre !== 'Invitado'
            || collect(['year_nace', 'genero_id', 'codigo', 'rol_id', 'email'])
                ->contains(fn (string $field): bool => $request->filled($field));

        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:32'],
            'apellidos' => ['required', 'string', 'max:32'],
            'cedula' => ['required', 'string', 'max:24', Rule::unique('usuarios', 'cedula')->ignore($usuario)],
            'estado_id' => ['required', 'integer', 'exists:estados,id'],
            'year_nace' => [$isCommunity ? 'required' : 'nullable', 'integer', 'between:1900,'.now()->year],
            'genero_id' => [$isCommunity ? 'required' : 'nullable', 'integer', 'exists:generos,id'],
            'codigo' => ['nullable', 'string', 'max:24', Rule::unique('usuarios', 'codigo')->ignore($usuario)],
            'rol_id' => [
                $isCommunity ? 'required' : 'nullable',
                'integer',
                Rule::exists('rols', 'id')->where(function (Builder $query) use ($isMaster, $usuario): void {
                    $query->where('nombre', '!=', 'Invitado');
                    if (! $isMaster) {
                        $query->where(function (Builder $query) use ($usuario): void {
                            $query->whereNotIn('nombre', self::HIDDEN_COMMUNITY_ROLES)
                                ->when($usuario->rol?->nombre !== 'Invitado', function (Builder $query) use ($usuario): void {
                                    $query->orWhere('id', $usuario->rol_id);
                                });
                        });
                    }
                }),
            ],
            'email' => [
                $isCommunity ? 'required' : 'nullable',
                'email',
                'max:128',
                Rule::unique('sesiones', 'email')->ignore($usuario->sesion?->id),
            ],
        ], [
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.unique' => 'Ya existe un usuario con esa cédula.',
            'estado_id.required' => 'Selecciona un estado.',
            'year_nace.required' => 'El año de nacimiento es obligatorio para usuarios de la comunidad.',
            'genero_id.required' => 'Selecciona un género para usuarios de la comunidad.',
            'rol_id.required' => 'Selecciona un rol para usuarios de la comunidad.',
            'rol_id.exists' => 'El rol seleccionado no está disponible.',
            'email.required' => 'El correo electrónico es obligatorio para usuarios de la comunidad.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ese correo electrónico ya está registrado.',
        ]);

        $isConvertingGuest = $isCommunity && (
            $usuario->rol?->nombre === 'Invitado'
            || $usuario->sesion === null
            || str_ends_with((string) $usuario->sesion?->email, '@example.invalid')
        );
        $temporaryPassword = $isConvertingGuest ? Str::upper(Str::random(6)) : null;

        try {
            DB::transaction(function () use ($validated, $usuario, $isCommunity, $isConvertingGuest, $temporaryPassword): void {
                $attributes = [
                    'nombres' => $validated['nombres'],
                    'apellidos' => $validated['apellidos'],
                    'cedula' => $validated['cedula'],
                    'estado_id' => $validated['estado_id'],
                ];

                if ($isCommunity) {
                    $codigo = $validated['codigo'] ?? $usuario->codigo;
                    if (! filled($codigo) || str_starts_with($codigo, 'INV-')) {
                        $codigo = 'USR-'.Str::upper(Str::random(20));
                    }

                    $attributes += [
                        'year_nace' => $validated['year_nace'],
                        'genero_id' => $validated['genero_id'],
                        'codigo' => $codigo,
                        'rol_id' => $validated['rol_id'],
                    ];
                }

                $usuario->update($attributes);

                if ($isCommunity) {
                    $session = $usuario->sesion;
                    if ($session === null) {
                        $session = $usuario->sesion()->make();
                    }
                    $session->email = $validated['email'];
                    if ($isConvertingGuest) {
                        $session->password = $temporaryPassword;
                    }
                    $session->save();
                }

                if ($isConvertingGuest) {
                    Mail::to($validated['email'])->send(
                        new UsuarioAccesoMail($validated['nombres'], $temporaryPassword)
                    );
                }
            });
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['email' => 'No se pudo enviar el correo. No se actualizaron los datos; verifica la configuración SMTP.']);
        }

        return to_route('usuarios.index')->with('success', 'El usuario se actualizó correctamente.');
    }

    public function create(): View
    {
        return view('usuarios.create', [
            'generos' => Genero::query()->orderBy('nombre')->get(['id', 'nombre']),
            'roles' => Rol::query()
                ->whereNotIn('nombre', self::HIDDEN_COMMUNITY_ROLES)
                ->orderByRaw("CASE WHEN nombre = 'Estudiante' THEN 0 ELSE 1 END")
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
        ]);
    }

    public function storeGuest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:32'],
            'apellidos' => ['required', 'string', 'max:32'],
            'cedula' => ['required', 'string', 'max:24', 'unique:usuarios,cedula'],
        ], [
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.unique' => 'Ya existe un usuario con esa cédula.',
        ]);

        $genderId = Genero::query()->where('nombre', 'Indefinido')->value('id');
        $activeStateId = Estado::query()->where('nombre', 'Activo')->value('id');
        $guestRoleId = Rol::query()
            ->whereIn('nombre', ['Invitado'])
            ->orderByRaw("CASE WHEN nombre = 'Invitado' THEN 0 ELSE 1 END")
            ->value('id');

        abort_if(
            $genderId === null || $activeStateId === null || $guestRoleId === null,
            500,
            'Faltan datos base de género, estado o rol para crear invitados.'
        );

        DB::transaction(function () use ($validated, $genderId, $activeStateId, $guestRoleId): void {
            $usuarioId = DB::table('usuarios')->insertGetId([
                'nombres' => $validated['nombres'],
                'apellidos' => $validated['apellidos'],
                'cedula' => $validated['cedula'],
                'codigo' => 'INV-'.Str::upper(Str::random(20)),
                'year_nace' => 0,
                'genero_id' => $genderId,
                'estado_id' => $activeStateId,
                'rol_id' => $guestRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('sesiones')->insert([
                'usuario_id' => $usuarioId,
                'email' => 'invitado+'.$usuarioId.'@example.invalid',
                'password' => Hash::make(Str::random(64)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Invitado creado con éxito.');
    }

    public function storeCommunity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:32'],
            'apellidos' => ['required', 'string', 'max:32'],
            'cedula' => ['required', 'string', 'max:24', 'unique:usuarios,cedula'],
            'year_nace' => ['required', 'integer', 'between:1900,'.now()->year],
            'genero_id' => ['required', 'integer', 'exists:generos,id'],
            'codigo' => ['nullable', 'string', 'max:24', 'unique:usuarios,codigo'],
            'rol_id' => [
                'required',
                'integer',
                Rule::exists('rols', 'id')->where(
                    fn (Builder $query) => $query->whereNotIn('nombre', self::HIDDEN_COMMUNITY_ROLES)
                ),
            ],
            'email' => ['required', 'email', 'max:128', 'unique:sesiones,email'],
        ], [
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.unique' => 'Ya existe un usuario con esa cédula.',
            'year_nace.required' => 'El año de nacimiento es obligatorio.',
            'year_nace.between' => 'El año de nacimiento debe estar entre 1900 y '.now()->year.'.',
            'genero_id.required' => 'Selecciona un género.',
            'codigo.unique' => 'Ese código ya está registrado.',
            'rol_id.required' => 'Selecciona un rol.',
            'rol_id.exists' => 'El rol seleccionado no está disponible.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ese correo electrónico ya está registrado.',
        ]);

        $activeStateId = Estado::query()->where('nombre', 'Activo')->value('id');

        abort_if($activeStateId === null, 500, 'No existe el estado Activo para crear usuarios.');

        $temporaryPassword = Str::upper(Str::random(6));

        try {
            DB::transaction(function () use ($validated, $activeStateId, $temporaryPassword): void {
                $usuarioId = DB::table('usuarios')->insertGetId([
                    'nombres' => $validated['nombres'],
                    'apellidos' => $validated['apellidos'],
                    'cedula' => $validated['cedula'],
                    'codigo' => $validated['codigo'] ?? 'USR-'.Str::upper(Str::random(20)),
                    'year_nace' => $validated['year_nace'],
                    'genero_id' => $validated['genero_id'],
                    'estado_id' => $activeStateId,
                    'rol_id' => $validated['rol_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('sesiones')->insert([
                    'usuario_id' => $usuarioId,
                    'email' => $validated['email'],
                    'password' => Hash::make($temporaryPassword),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Mail::to($validated['email'])->send(
                    new UsuarioAccesoMail($validated['nombres'], $temporaryPassword)
                );
            });
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['email' => 'No se pudo enviar el correo. No se creó el usuario; verifica la configuración SMTP.']);
        }

        return back()->with('success', 'Usuario de la comunidad creado. Se enviaron sus credenciales al correo registrado.');
    }
}
