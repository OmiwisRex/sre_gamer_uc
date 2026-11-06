<?php

namespace App\Http\Controllers;

use App\Models\Sesion;
use App\Services\UsuarioAccesoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SesionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:128'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Las credenciales ingresadas no son válidas.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('index'));
    }

    public function recover(Request $request, UsuarioAccesoService $access): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:128'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
        ]);

        $sesion = Sesion::query()
            ->with('usuario:id,nombres,apellidos')
            ->whereHas('usuario.rol', function (Builder $query): void {
                $query->where('nombre', '!=', 'Invitado');
            })
            ->where('email', $validated['email'])
            ->first();

        if ($sesion !== null) {
            try {
                DB::transaction(function () use ($access, $sesion): void {
                    $access->sendTemporaryPassword(
                        $sesion,
                        trim($sesion->usuario->nombres.' '.$sesion->usuario->apellidos),
                    );
                });
            } catch (TransportExceptionInterface $exception) {
                report($exception);

                return back()
                    ->withInput()
                    ->withErrors(['email' => 'No se pudo enviar el correo. Verifica la configuración SMTP.']);
            }
        }

        return back()->with('success', 'Si el correo está registrado, recibirás una nueva contraseña.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('sesion.login')->with('success', 'Has cerrado sesión correctamente.');
    }
}
