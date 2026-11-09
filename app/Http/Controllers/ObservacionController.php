<?php

namespace App\Http\Controllers;

use App\Models\Asociacion;
use App\Models\Dispositivo;
use App\Models\Evento;
use App\Models\Observacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ObservacionController extends Controller
{
    public function index(): View
    {
        return view('observaciones.index', [
            'eventos' => Evento::query()
                ->whereDoesntHave('estado', function (Builder $query): void {
                    $query->where('nombre', 'Eliminado');
                })
                ->orderByDesc('inicio')
                ->orderByDesc('id')
                ->get(['id', 'nombre']),
            'dispositivos' => Dispositivo::query()
                ->whereHas('estado', function (Builder $query): void {
                    $query->where('nombre', 'Activo');
                })
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'asociacionesRecientes' => Asociacion::query()
                ->with([
                    'usuario:id,nombres,apellidos',
                    'dispositivo:id,nombre',
                    'evento:id,nombre',
                ])
                ->whereBetween('inicio', [now()->subDay(), now()])
                ->orderByDesc('inicio')
                ->orderByDesc('id')
                ->get(['id', 'usuario_id', 'dispositivo_id', 'evento_id', 'inicio']),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'admin_cedula' => ['nullable', 'string', 'max:24'],
            'usuario_cedula' => ['nullable', 'string', 'max:24'],
            'evento_id' => ['nullable', 'integer', 'exists:eventos,id'],
            'dispositivo_id' => ['nullable', 'integer', 'exists:dispositivos,id'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'con_asociacion' => ['nullable', Rule::in(['0', '1'])],
            'cursor' => ['nullable', 'string', 'max:2048'],
        ]);

        $administratorIdentification = trim((string) ($validated['admin_cedula'] ?? ''));
        $playerIdentification = trim((string) ($validated['usuario_cedula'] ?? ''));
        $observaciones = Observacion::query()
            ->when($administratorIdentification !== '', function (Builder $query) use ($administratorIdentification): void {
                $query->whereHas('usuario', function (Builder $query) use ($administratorIdentification): void {
                    $query->where('cedula', 'like', "%{$administratorIdentification}%");
                });
            })
            ->when($playerIdentification !== '', function (Builder $query) use ($playerIdentification): void {
                $query->whereHas('asociacion.usuario', function (Builder $query) use ($playerIdentification): void {
                    $query->where('cedula', 'like', "%{$playerIdentification}%")
                        ->orWhere('codigo', 'like', "%{$playerIdentification}%");
                });
            })
            ->when(isset($validated['evento_id']), function (Builder $query) use ($validated): void {
                $query->whereHas('asociacion', function (Builder $query) use ($validated): void {
                    $query->where('evento_id', $validated['evento_id']);
                });
            })
            ->when(isset($validated['dispositivo_id']), function (Builder $query) use ($validated): void {
                $query->whereHas('asociacion', function (Builder $query) use ($validated): void {
                    $query->where('dispositivo_id', $validated['dispositivo_id']);
                });
            })
            ->when(isset($validated['fecha']), function (Builder $query) use ($validated): void {
                $query->whereDate('created_at', $validated['fecha']);
            })
            ->when(isset($validated['con_asociacion']), function (Builder $query) use ($validated): void {
                if ($validated['con_asociacion'] === '1') {
                    $query->whereNotNull('asociacion_id');
                } else {
                    $query->whereNull('asociacion_id');
                }
            })
            ->select(['id', 'mensaje', 'asociacion_id', 'created_at'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(config('app.observaciones_cursor_size', 20));

        return response()->json([
            'data' => $observaciones->getCollection()
                ->map(fn (Observacion $observacion): array => [
                    'id' => $observacion->id,
                    'fecha' => Carbon::parse($observacion->created_at)->format('d/m/Y H:i'),
                    'mensaje' => $observacion->mensaje,
                    'relacion' => $observacion->asociacion_id ? 'Asociada' : 'General',
                ])
                ->values(),
            'next_cursor' => $observaciones->nextCursor()?->encode(),
            'has_more' => $observaciones->hasMorePages(),
        ]);
    }

    public function detail(Observacion $observacion): JsonResponse
    {
        $observacion->load([
            'usuario:id,nombres,apellidos',
            'asociacion.usuario:id,nombres,apellidos',
            'asociacion.dispositivo:id,nombre',
            'asociacion.evento:id,nombre',
        ]);

        $asociacion = $observacion->asociacion;

        return response()->json([
            'data' => [
                'fecha' => Carbon::parse($observacion->created_at)->format('d/m/Y H:i'),
                'mensaje' => $observacion->mensaje,
                'administrador' => trim(($observacion->usuario?->nombres ?? '').' '.($observacion->usuario?->apellidos ?? '')) ?: 'No disponible',
                'jugador' => trim(($asociacion?->usuario?->nombres ?? '').' '.($asociacion?->usuario?->apellidos ?? '')) ?: 'Sin asociación',
                'dispositivo' => $asociacion?->dispositivo?->nombre ?? 'No aplica',
                'evento' => $asociacion?->evento?->nombre ?? 'No aplica',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mensaje' => ['required', 'string', 'max:256'],
            'asociacion_id' => [
                'nullable',
                'integer',
                Rule::exists('asociaciones', 'id')->where(function (QueryBuilder $query): void {
                    $query->whereBetween('inicio', [now()->subDay(), now()]);
                }),
            ],
        ], [
            'mensaje.required' => 'El mensaje de la observación es obligatorio.',
            'mensaje.max' => 'El mensaje no puede superar los 256 caracteres.',
            'asociacion_id.exists' => 'La asociación seleccionada no está entre las asociaciones recientes.',
        ]);

        Observacion::query()->create([
            'usuario_id' => $request->user()->usuario_id,
            'mensaje' => $validated['mensaje'],
            'asociacion_id' => $validated['asociacion_id'] ?? null,
        ]);

        return to_route('observaciones.index')->with('success', 'La observación se creó correctamente.');
    }
}
