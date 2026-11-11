<?php

namespace App\Http\Controllers;

use App\Models\Accion;
use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(): View
    {
        return view('auditorias.index', [
            'acciones' => Accion::query()->orderBy('nombre')->get(['id', 'nombre']),
            'tablas' => Auditoria::query()->distinct()->orderBy('tabla')->pluck('tabla'),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cedula' => ['nullable', 'string', 'max:24'],
            'nombre' => ['nullable', 'string', 'max:100'],
            'accion_id' => ['nullable', 'integer', 'exists:acciones,id'],
            'tabla' => ['nullable', 'string', 'max:32'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'cursor' => ['nullable', 'string', 'max:2048'],
        ]);

        $cedula = trim((string) ($validated['cedula'] ?? ''));
        $nombre = trim((string) ($validated['nombre'] ?? ''));
        $auditorias = Auditoria::query()
            ->with(['usuario:id,nombres,apellidos,cedula', 'accion:id,nombre'])
            ->when($cedula !== '', function (Builder $query) use ($cedula): void {
                $query->whereHas('usuario', fn (Builder $query) => $query->where('cedula', 'like', "%{$cedula}%"));
            })
            ->when($nombre !== '', function (Builder $query) use ($nombre): void {
                $query->whereHas('usuario', function (Builder $query) use ($nombre): void {
                    foreach (preg_split('/\s+/', $nombre) as $part) {
                        $query->where(function (Builder $query) use ($part): void {
                            $query->where('nombres', 'like', "%{$part}%")
                                ->orWhere('apellidos', 'like', "%{$part}%");
                        });
                    }
                });
            })
            ->when(isset($validated['accion_id']), fn (Builder $query) => $query->where('accion_id', $validated['accion_id']))
            ->when(isset($validated['tabla']), fn (Builder $query) => $query->where('tabla', $validated['tabla']))
            ->when(isset($validated['fecha']), fn (Builder $query) => $query->whereDate('created_at', $validated['fecha']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(config('app.auditorias_cursor_size', 20));

        return response()->json([
            'data' => $auditorias->getCollection()->map(fn (Auditoria $auditoria): array => [
                'id' => $auditoria->id,
                'fecha' => $auditoria->created_at->format('d/m/Y - h:i A'),
                'accion' => $auditoria->accion?->nombre ?? 'Sin acción',
                'tabla' => $auditoria->tabla,
                'indice' => $auditoria->indice,
                'usuario' => trim(($auditoria->usuario?->nombres ?? '').' '.($auditoria->usuario?->apellidos ?? '')) ?: 'No disponible',
                'cedula' => $auditoria->usuario?->cedula,
            ])->values(),
            'next_cursor' => $auditorias->nextCursor()?->encode(),
            'has_more' => $auditorias->hasMorePages(),
        ]);
    }
}
