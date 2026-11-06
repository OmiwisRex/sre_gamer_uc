<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Estado;
use App\Models\TipoDispo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DispositivoController extends Controller
{
    public function index(): View
    {
        return view('dispositivos.index', [
            'tiposDispo' => TipoDispo::query()->orderBy('nombre')->get(['id', 'nombre']),
            'estados' => Estado::query()
                ->whereHas('dispositivos')
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo_dispo_id' => ['nullable', 'integer', 'exists:tipo_dispos,id'],
            'estado_id' => ['nullable', 'integer', 'exists:estados,id'],
            'cursor' => ['nullable', 'string', 'max:2048'],
        ]);

        $dispositivos = Dispositivo::query()
            ->with(['estado:id,nombre', 'tipoDispo:id,nombre'])
            ->when(isset($validated['tipo_dispo_id']), function (Builder $query) use ($validated): void {
                $query->where('tipo_dispo_id', $validated['tipo_dispo_id']);
            })
            ->when(isset($validated['estado_id']), function (Builder $query) use ($validated): void {
                $query->where('estado_id', $validated['estado_id']);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(config('app.dispositivos_cursor_size', 20));

        return response()->json([
            'data' => $dispositivos->getCollection()
                ->map(fn (Dispositivo $dispositivo): array => [
                    'id' => $dispositivo->id,
                    'nombre' => $dispositivo->nombre,
                    'estado' => $dispositivo->estado?->nombre,
                    'tipo' => $dispositivo->tipoDispo?->nombre,
                    'edit_url' => url('/dispositivos/'.$dispositivo->id.'/edit'),
                ])
                ->values(),
            'next_cursor' => $dispositivos->nextCursor()?->encode(),
            'has_more' => $dispositivos->hasMorePages(),
        ]);
    }

    public function detail(Dispositivo $dispositivo): JsonResponse
    {
        $dispositivo->load(['estado:id,nombre', 'tipoDispo:id,nombre']);

        return response()->json([
            'data' => [
                'nombre' => $dispositivo->nombre,
                'tipo' => $dispositivo->tipoDispo?->nombre,
                'estado' => $dispositivo->estado?->nombre,
                'descripcion' => $dispositivo->descripcion,
                'direccion' => $dispositivo->direccion,
            ],
        ]);
    }

    public function edit(Dispositivo $dispositivo): View
    {
        return view('dispositivos.edit', [
            'dispositivo' => $dispositivo,
            'tiposDispo' => TipoDispo::query()->orderBy('nombre')->get(['id', 'nombre']),
            'estados' => Estado::query()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, Dispositivo $dispositivo): RedirectResponse
    {
        $isMaster = $request->user()?->usuario?->rol?->nombre === 'Master';
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:24', Rule::unique('dispositivos', 'nombre')->ignore($dispositivo)],
            'tipo_dispo_id' => ['required', 'integer', 'exists:tipo_dispos,id'],
            'estado_id' => [
                'required',
                'integer',
                Rule::exists('estados', 'id')->where(function (QueryBuilder $query) use ($isMaster, $dispositivo): void {
                    if (! $isMaster) {
                        $query->where(function (QueryBuilder $query) use ($dispositivo): void {
                            $query->where('nombre', '!=', 'Eliminado')
                                ->orWhere('id', $dispositivo->estado_id);
                        });
                    }
                }),
            ],
            'descripcion' => ['nullable', 'string', 'max:256'],
        ], [
            'nombre.required' => 'El nombre del dispositivo es obligatorio.',
            'nombre.unique' => 'Ya existe un dispositivo con ese nombre.',
            'tipo_dispo_id.required' => 'Selecciona un tipo de dispositivo.',
            'estado_id.required' => 'Selecciona un estado.',
            'estado_id.exists' => 'El estado no existe o solo el administrador Master puede eliminar un dispositivo.',
        ]);

        $dispositivo->update([
            'nombre' => $validated['nombre'],
            'tipo_dispo_id' => $validated['tipo_dispo_id'],
            'estado_id' => $validated['estado_id'],
            'descripcion' => $validated['descripcion'] ?? '',
        ]);

        return to_route('dispositivos.index')->with('success', 'El dispositivo se actualizó correctamente.');
    }

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
