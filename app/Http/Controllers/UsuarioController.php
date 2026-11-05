<?php

namespace App\Http\Controllers;

use App\Mail\UsuarioAccesoMail;
use App\Models\Estado;
use App\Models\Genero;
use App\Models\Rol;
use Illuminate\Database\Query\Builder;
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
