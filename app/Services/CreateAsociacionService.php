<?php

namespace App\Services;

use App\Models\Asociacion;
use App\Models\Estado;
use App\Models\Observacion;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAsociacionService
{
    /**
     * @param array{
     *     dispositivo_id: int|null,
     *     evento_id: int|null,
     *     reserva_id: int|null
     * } $attributes
     */
    public function handle(int $usuarioId, int $administradorId, array $attributes, ?string $mensaje): Asociacion
    {
        return DB::transaction(function () use ($usuarioId, $administradorId, $attributes, $mensaje): Asociacion {
            $usuario = Usuario::query()->lockForUpdate()->findOrFail($usuarioId);
            $hasNonDeletedAssociation = $usuario->asociaciones()
                ->whereHas('estado', function (Builder $query): void {
                    $query->where('nombre', '!=', 'Eliminado');
                })
                ->exists();

            if ($hasNonDeletedAssociation) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'El jugador ya tiene una asociación que debe eliminarse antes de crear otra.',
                ]);
            }

            if ($attributes['reserva_id'] !== null) {
                $hasActiveReservation = $usuario->reservas()
                    ->whereKey($attributes['reserva_id'])
                    ->whereHas('estado', function (Builder $query): void {
                        $query->where('nombre', 'Activo');
                    })
                    ->exists();

                if (! $hasActiveReservation) {
                    throw ValidationException::withMessages([
                        'reserva_id' => 'La reserva seleccionada no está activa para este jugador.',
                    ]);
                }
            }

            $activeState = Estado::query()->where('nombre', 'Activo')->firstOrFail();
            $inicio = now();
            $asociacion = $usuario->asociaciones()->create([
                ...$attributes,
                'estado_id' => $activeState->id,
                'inicio' => $inicio,
                'final' => $inicio,
            ]);

            $mensaje = trim($mensaje ?? '');

            if ($mensaje !== '') {
                Observacion::query()->create([
                    'usuario_id' => $administradorId,
                    'mensaje' => $mensaje,
                    'asociacion_id' => $asociacion->id,
                ]);
            }

            return $asociacion;
        });
    }
}
