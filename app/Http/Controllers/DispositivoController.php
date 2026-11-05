<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Estado;
use App\Models\TipoDispo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DispositivoController extends Controller
{
    public function create(): View
    {
        return view('dispositivos.create', [
            'tiposDispo' => TipoDispo::query()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:24', 'unique:dispositivos,nombre'],
            'tipo_dispo_id' => ['required', 'integer', 'exists:tipo_dispos,id'],
            'descripcion' => ['nullable', 'string', 'max:256'],
        ], [
            'nombre.required' => 'El nombre del dispositivo es obligatorio.',
            'nombre.unique' => 'Ya existe un dispositivo con ese nombre.',
            'tipo_dispo_id.required' => 'Selecciona un tipo de dispositivo.',
        ]);

        $activeState = Estado::query()->where('nombre', 'Activo')->firstOrFail();

        Dispositivo::query()->create([
            'nombre' => $validated['nombre'],
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $validated['tipo_dispo_id'],
            'estado_id' => $activeState->id,
            'descripcion' => $validated['descripcion'] ?? '',
        ]);

        return to_route('dispositivos.create')->with('success', 'El dispositivo se creó correctamente.');
    }
}
