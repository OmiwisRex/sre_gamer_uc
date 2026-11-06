<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.current_password' => 'La contraseña actual no es válida.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'new_password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        $session = $request->user();
        $session->password = $validated['new_password'];
        $session->save();

        return to_route('configuracion.index')->with('success', 'La contraseña se actualizó correctamente.');
    }
}
