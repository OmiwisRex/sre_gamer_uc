<?php

namespace App\Services;

use App\Models\Estado;
use App\Models\Evento;
use Illuminate\Support\Facades\DB;

class CreateEventoService
{
    /**
     * @param array{
     *     nombre: string,
     *     descripcion: string,
     *     usuario_id: int,
     *     inicio: string,
     *     duracion_horas: int,
     *     aforo: int,
     *     auto_admin: bool // false los jugadres reservan
     * } $attributes
     * @param  array<int, int|string>  $deviceIds
     */
    public function handle(array $attributes, array $deviceIds): Evento
    {
        return DB::transaction(function () use ($attributes, $deviceIds): Evento {
            $activeState = Estado::query()->where('nombre', 'Activo')->firstOrFail();

            // TODO: Check event and selected-device schedule collisions before persistence.
            $event = Evento::query()->create([
                ...$attributes,
                'estado_id' => $activeState->id,
            ]);

            $event->dispositivos()->sync($deviceIds);

            return $event;
        });
    }
}
