<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Estado;
use App\Models\Evento;
use App\Models\Usuario;
use App\Services\CreateEventoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventoController extends Controller
{
    public function index(): View
    {
        return view('eventos.index', [
            'estados' => Estado::query()
                ->whereHas('eventos')
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'usuario_cedula' => ['nullable', 'string', 'max:24'],
            'estado_id' => ['nullable', 'integer', 'exists:estados,id'],
            'cursor' => ['nullable', 'string', 'max:2048'],
        ]);

        $organizerIdentification = trim((string) ($validated['usuario_cedula'] ?? ''));
        $eventos = Evento::query()
            ->with(['estado:id,nombre'])
            ->when($organizerIdentification !== '', function (Builder $query) use ($organizerIdentification): void {
                $query->whereHas('usuario', function (Builder $query) use ($organizerIdentification): void {
                    $query->where('cedula', 'like', "%{$organizerIdentification}%");
                });
            })
            ->when(isset($validated['estado_id']), function (Builder $query) use ($validated): void {
                $query->where('estado_id', $validated['estado_id']);
            })
            ->orderByDesc('inicio')
            ->orderByDesc('id')
            ->cursorPaginate(config('app.eventos_cursor_size', 20));

        return response()->json([
            'data' => $eventos->getCollection()
                ->map(fn (Evento $evento): array => [
                    'id' => $evento->id,
                    'nombre' => $evento->nombre,
                    'estado' => $evento->estado?->nombre,
                    'inicio' => $evento->inicio
                        ? Carbon::parse($evento->inicio)->format('d/m/Y - h:i A')
                        : null,
                    'aforo' => $evento->aforo,
                    'edit_url' => url('/eventos/'.$evento->id.'/edit'),
                ])
                ->values(),
            'next_cursor' => $eventos->nextCursor()?->encode(),
            'has_more' => $eventos->hasMorePages(),
        ]);
    }

    public function detail(Evento $evento): JsonResponse
    {
        $evento->load([
            'estado:id,nombre',
            'usuario:id,nombres,apellidos',
            'dispositivos:id,nombre',
        ]);

        return response()->json([
            'data' => [
                'nombre' => $evento->nombre,
                'descripcion' => $evento->descripcion,
                'organizador' => trim(($evento->usuario?->nombres ?? '').' '.($evento->usuario?->apellidos ?? '')) ?: null,
                'inicio' => $evento->inicio
                    ? Carbon::parse($evento->inicio)->format('d/m/Y - h:i A')
                    : null,
                'duracion' => $evento->duracion_horas.' horas',
                'aforo' => $evento->aforo.' personas',
                'estado' => $evento->estado?->nombre,
                'administracion' => $evento->auto_admin ? 'Auto administrado' : 'Administrado por encargados',
                'dispositivos' => $evento->dispositivos->pluck('nombre')->all(),
            ],
        ]);
    }

    public function edit(Evento $evento): View
    {
        $evento->load(['usuario:id,cedula', 'dispositivos:id']);

        return view('eventos.edit', [
            'evento' => $evento,
            'estados' => Estado::query()->orderBy('nombre')->get(['id', 'nombre']),
            'dispositivos' => Dispositivo::query()
                ->with('tipoDispo:id,nombre')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'tipo_dispo_id']),
        ]);
    }

    public function update(Request $request, Evento $evento, CreateEventoService $eventService): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:32', Rule::unique('eventos', 'nombre')->ignore($evento)],
            'organizador_cedula' => ['required', 'string', 'max:24', 'exists:usuarios,cedula'],
            'inicio' => ['required', 'date'],
            'duracion_horas' => ['required', 'integer', 'min:1', 'max:24'],
            'aforo' => ['required', 'integer', 'min:1'],
            'estado_id' => ['required', 'integer', 'exists:estados,id'],
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
            'estado_id.required' => 'Selecciona un estado.',
            'dispositivos.*.exists' => 'Uno de los dispositivos seleccionados ya no está disponible.',
        ]);

        $organizer = Usuario::query()
            ->where('cedula', $validated['organizador_cedula'])
            ->firstOrFail();

        $eventService->update($evento, [
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? '',
            'usuario_id' => $organizer->id,
            'inicio' => Carbon::parse($validated['inicio'])->toDateTimeString(),
            'duracion_horas' => $validated['duracion_horas'],
            'aforo' => $validated['aforo'],
            'estado_id' => $validated['estado_id'],
            'auto_admin' => $validated['auto_admin'],
        ], $validated['dispositivos'] ?? []);

        return to_route('eventos.index')->with('success', 'El evento se actualizó correctamente.');
    }

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

    public function expire(): JsonResponse
    {
        $deletedState = Estado::query()->where('nombre', 'Eliminado')->firstOrFail();
        $now = now();

        Evento::query()
            ->where('estado_id', '!=', $deletedState->id)
            ->where('inicio', '<=', $now)
            ->chunkById(100, function ($events) use ($deletedState, $now): void {
                foreach ($events as $event) {
                    if (Carbon::parse($event->inicio)->addHours((int) $event->duracion_horas)->lte($now)) {
                        Evento::query()
                            ->whereKey($event->id)
                            ->where('estado_id', $event->estado_id)
                            ->where('inicio', $event->inicio)
                            ->where('duracion_horas', $event->duracion_horas)
                            ->update(['estado_id' => $deletedState->id]);
                    }
                }
            });

        return response()->json(['ok' => true]);
    }
}
