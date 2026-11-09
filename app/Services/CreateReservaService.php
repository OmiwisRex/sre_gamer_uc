<?php

namespace App\Services;

use App\Models\Estado;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateReservaService
{
    /**
     * @param array{
     *     tipo_dispo_id: int,
     *     evento_id: int|null,
     *     inicio: string,
     *     duracion_min: int
     * } $attributes
     */
    public function handle(int $usuarioId, array $attributes): Reserva
    {
        return DB::transaction(function () use ($usuarioId, $attributes): Reserva {
            $usuario = Usuario::query()->lockForUpdate()->findOrFail($usuarioId);
            $hasActiveReservation = $usuario->reservas()
                ->whereHas('estado', function (Builder $query): void {
                    $query->where('nombre', 'Activo');
                })
                ->exists();

            if ($hasActiveReservation) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'El jugador ya tiene una reserva activa.',
                ]);
            }

            $activeState = Estado::query()->where('nombre', 'Activo')->firstOrFail();

            // TODO: Add schedule collision checks when the reservation rules are implemented.
            return $usuario->reservas()->create([
                ...$attributes,
                'estado_id' => $activeState->id,
            ]);
        });
    }
}
