<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Usuario;
use App\Services\CreateEventoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EventoController extends Controller
{
    public function create(): View
    {
        return view('eventos.create', [
            'dispositivos' => Dispositivo::query()
                ->with('tipoDispo:id,nombre')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'tipo_dispo_id']),
        ]);
    }

    public function store(Request $request, CreateEventoService $createEvento): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:32', 'unique:eventos,nombre'],
            'organizador_cedula' => ['required', 'string', 'max:24', 'exists:usuarios,cedula'],
            'inicio' => ['required', 'date'],
            'duracion_horas' => ['required', 'integer', 'min:1', 'max:24'],
            'aforo' => ['required', 'integer', 'min:1'],
            'auto_admin' => ['required', 'boolean'],
            'descripcion' => ['nullable', 'string', 'max:256'],
            'dispositivos' => ['nullable', 'array'],
            'dispositivos.*' => ['required', 'integer', 'distinct', 'exists:dispositivos,id'],
        ], [
            'nombre.required' => 'El nombre del evento es obligatorio.',
            'organizador_cedula.required' => 'La cédula del organizador es obligatoria.',
            'organizador_cedula.exists' => 'No se encontró un usuario con esa cédula.',
            'inicio.required' => 'La fecha y hora de inicio son obligatorias.',
            'duracion_horas.required' => 'La duración es obligatoria.',
            'duracion_horas.integer' => 'La duración debe ser un número entero de horas.',
            'duracion_horas.min' => 'La duración mínima es de una hora.',
            'duracion_horas.max' => 'La duración máxima es de 24 horas.',
            'aforo.required' => 'El aforo aproximado es obligatorio.',
            'aforo.integer' => 'El aforo debe ser un número entero.',
            'aforo.min' => 'El aforo debe ser mayor que cero.',
            'dispositivos.*.exists' => 'Uno de los dispositivos seleccionados ya no está disponible.',
        ]);

        $organizer = Usuario::query()
            ->where('cedula', $validated['organizador_cedula'])
            ->firstOrFail();

        $createEvento->handle([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? '',
            'usuario_id' => $organizer->id,
            'inicio' => Carbon::parse($validated['inicio'])->toDateTimeString(),
            'duracion_horas' => $request->integer('duracion_horas'),
            'aforo' => $request->integer('aforo'),
            'auto_admin' => $request->boolean('auto_admin'),
        ], $validated['dispositivos'] ?? []);

        return to_route('eventos.create')->with('success', 'El evento se creó correctamente.');
    }
}
