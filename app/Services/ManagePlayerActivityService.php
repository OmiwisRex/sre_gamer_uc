<?php

namespace App\Services;

use App\Models\Asociacion;
use App\Models\Estado;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManagePlayerActivityService
{
    public function beginReservationConversion(int $reservaId): Reserva
    {
        $reservationOwnerId = Reserva::query()->findOrFail($reservaId)->usuario_id;

        return DB::transaction(function () use ($reservaId, $reservationOwnerId): Reserva {
            $usuario = Usuario::query()->lockForUpdate()->findOrFail($reservationOwnerId);
            $reserva = Reserva::query()->lockForUpdate()->findOrFail($reservaId);
            $estadoActivo = Estado::query()->where('nombre', 'Activo')->firstOrFail();
            $estadoBloqueado = Estado::query()->where('nombre', 'Bloqueado')->firstOrFail();

            if (! $usuario->estado()->where('nombre', 'Activo')->exists()) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'Solo se pueden asociar jugadores en estado Activo.',
                ]);
            }

            if ($reserva->estado_id !== $estadoActivo->id) {
                throw ValidationException::withMessages([
                    'reserva_id' => 'Solo se puede iniciar la conversión de una reserva Activa.',
                ]);
            }

            $now = now(config('app.timezone'));
            $reservationStart = Carbon::parse($reserva->inicio, config('app.timezone'));

            if ($now->lt($reservationStart->copy()->subMinutes(15)) || $now->gt($reservationStart->copy()->addMinutes(15))) {
                throw ValidationException::withMessages([
                    'reserva_id' => 'La reserva solo puede asociarse entre 15 minutos antes y 15 minutos después de su inicio.',
                ]);
            }

            $reserva->update(['estado_id' => $estadoBloqueado->id]);

            return $reserva->refresh();
        }, 3);
    }

    public function cancelReservation(int $reservaId): Reserva
    {
        return $this->updateReservationState($reservaId, ['Activo', 'Bloqueado'], 'Eliminado');
    }

    public function cancelReservationConversion(int $reservaId): Reserva
    {
        return $this->updateReservationState($reservaId, ['Bloqueado'], 'Activo');
    }

    public function closeAssociation(int $asociacionId): Asociacion
    {
        $associationOwnerId = Asociacion::query()->findOrFail($asociacionId)->usuario_id;

        return DB::transaction(function () use ($asociacionId, $associationOwnerId): Asociacion {
            Usuario::query()->lockForUpdate()->findOrFail($associationOwnerId);
            $asociacion = Asociacion::query()->lockForUpdate()->findOrFail($asociacionId);
            $states = Estado::query()
                ->whereIn('nombre', ['Activo', 'Bloqueado', 'Eliminado'])
                ->get(['id', 'nombre'])
                ->keyBy('nombre');
            $estadoActivo = $states->get('Activo');
            $estadoBloqueado = $states->get('Bloqueado');
            $estadoEliminado = $states->get('Eliminado');

            if ($estadoActivo === null || $estadoBloqueado === null || $estadoEliminado === null) {
                throw ValidationException::withMessages([
                    'asociacion_id' => 'No se encontraron los estados necesarios para cerrar la asociación.',
                ]);
            }

            if (! in_array($asociacion->estado_id, [$estadoActivo->id, $estadoBloqueado->id], true)) {
                throw ValidationException::withMessages([
                    'asociacion_id' => 'Solo se pueden cerrar asociaciones Activas o Bloqueadas.',
                ]);
            }

            if ($asociacion->estado_id === $estadoActivo->id) {
                $asociacion->final = now(config('app.timezone'));
            }

            $asociacion->estado_id = $estadoEliminado->id;
            $asociacion->save();

            return $asociacion;
        }, 3);
    }

    public function continueAssociation(int $asociacionId): Asociacion
    {
        $associationOwnerId = Asociacion::query()->findOrFail($asociacionId)->usuario_id;

        return DB::transaction(function () use ($asociacionId, $associationOwnerId): Asociacion {
            Usuario::query()->lockForUpdate()->findOrFail($associationOwnerId);
            $asociacion = Asociacion::query()->lockForUpdate()->findOrFail($asociacionId);
            $estadoBloqueado = Estado::query()->where('nombre', 'Bloqueado')->firstOrFail();
            $estadoActivo = Estado::query()->where('nombre', 'Activo')->firstOrFail();

            if ($asociacion->estado_id !== $estadoBloqueado->id) {
                throw ValidationException::withMessages([
                    'asociacion_id' => 'Solo se pueden continuar asociaciones Bloqueadas.',
                ]);
            }

            $asociacion->update([
                'estado_id' => $estadoActivo->id,
                'final' => now(config('app.timezone')),
            ]);

            return $asociacion;
        }, 3);
    }

    public function syncActiveAssociationEndTimesForUser(Usuario $usuario): void
    {
        $estadoActivo = Estado::query()->where('nombre', 'Activo')->firstOrFail();
        $now = now(config('app.timezone'));

        $usuario->asociaciones()
            ->where('estado_id', $estadoActivo->id)
            ->update([
                'final' => $now,
                'updated_at' => $now,
            ]);
    }

    /**
     * @param  array<int, string>  $allowedStateNames
     */
    private function updateReservationState(int $reservaId, array $allowedStateNames, string $nextStateName): Reserva
    {
        $reservationOwnerId = Reserva::query()->findOrFail($reservaId)->usuario_id;

        return DB::transaction(function () use ($reservaId, $reservationOwnerId, $allowedStateNames, $nextStateName): Reserva {
            Usuario::query()->lockForUpdate()->findOrFail($reservationOwnerId);
            $reserva = Reserva::query()
                ->with('estado:id,nombre')
                ->lockForUpdate()
                ->findOrFail($reservaId);

            if (! in_array($reserva->estado?->nombre, $allowedStateNames, true)) {
                throw ValidationException::withMessages([
                    'reserva_id' => 'La reserva ya no está en un estado que permita esta operación.',
                ]);
            }

            $nextState = Estado::query()->where('nombre', $nextStateName)->firstOrFail();
            $reserva->update(['estado_id' => $nextState->id]);

            return $reserva;
        }, 3);
    }
}
