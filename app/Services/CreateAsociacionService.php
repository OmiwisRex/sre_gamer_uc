<?php

namespace App\Services;

use App\Models\Accion;
use App\Models\Asociacion;
use App\Models\Auditoria;
use App\Models\Dispositivo;
use App\Models\Estado;
use App\Models\Evento;
use App\Models\Observacion;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAsociacionService
{
    /**
     * @param array{
     *     dispositivo_id: int|null,
     *     evento_id: int|null,
     *     reserva_id: int|null,
     *     confirmar_compartido: bool,
     *     confirmar_reserva: bool
     * } $attributes
     */
    public function handle(int $usuarioId, int $administradorId, array $attributes, ?string $mensaje): Asociacion
    {
        return DB::transaction(function () use ($usuarioId, $administradorId, $attributes, $mensaje): Asociacion {
            $usuario = Usuario::query()->lockForUpdate()->findOrFail($usuarioId);
            $estados = Estado::query()
                ->whereIn('nombre', ['Activo', 'Bloqueado', 'Eliminado'])
                ->get(['id', 'nombre'])
                ->keyBy('nombre');
            $estadoActivo = $estados->get('Activo');
            $estadoBloqueado = $estados->get('Bloqueado');
            $estadoEliminado = $estados->get('Eliminado');

            if ($estadoActivo === null || ! $usuario->estado()->where('nombre', 'Activo')->exists()) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'Solo se pueden asociar jugadores en estado Activo.',
                ]);
            }

            $estadoIdsVigentes = collect([$estadoActivo->id, $estadoBloqueado?->id])->filter()->all();
            $hasCurrentAssociation = $usuario->asociaciones()
                ->whereIn('estado_id', $estadoIdsVigentes)
                ->exists();

            if ($hasCurrentAssociation) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'El jugador ya tiene una asociación Activa o Bloqueada que debe resolver antes de crear otra.',
                ]);
            }

            $reserva = null;
            if ($attributes['reserva_id'] !== null) {
                $reserva = $usuario->reservas()
                    ->lockForUpdate()
                    ->with('estado:id,nombre')
                    ->find($attributes['reserva_id']);

                if ($reserva === null || $reserva->estado?->nombre !== 'Bloqueado') {
                    throw ValidationException::withMessages([
                        'reserva_id' => 'La reserva debe pertenecer al jugador y estar Bloqueada para convertirla en asociación.',
                    ]);
                }

                $now = now(config('app.timezone'));
                $reservationStart = Carbon::parse($reserva->inicio, config('app.timezone'));

                if ($now->lt($reservationStart->copy()->subMinutes(15)) || $now->gt($reservationStart->copy()->addMinutes(15))) {
                    throw ValidationException::withMessages([
                        'reserva_id' => 'La reserva solo puede convertirse en asociación entre 15 minutos antes y 15 minutos después de su inicio.',
                    ]);
                }

                if ($attributes['evento_id'] !== $reserva->evento_id) {
                    throw ValidationException::withMessages([
                        'evento_id' => 'El evento de la asociación debe coincidir con el de la reserva.',
                    ]);
                }
            }

            $now = now(config('app.timezone'));
            $evento = null;
            if ($attributes['evento_id'] !== null) {
                $evento = Evento::query()
                    ->with('estado:id,nombre')
                    ->lockForUpdate()
                    ->findOrFail($attributes['evento_id']);
                $eventStart = Carbon::parse($evento->inicio, config('app.timezone'));
                $eventEnd = $eventStart->copy()->addHours((int) $evento->duracion_horas);

                if ($evento->estado?->nombre !== 'Activo' || $now->lt($eventStart) || $now->gte($eventEnd)) {
                    throw ValidationException::withMessages([
                        'evento_id' => 'El evento debe estar Activo y en curso para crear una asociación.',
                    ]);
                }
            }

            $dispositivo = null;
            if ($attributes['dispositivo_id'] !== null) {
                $dispositivo = Dispositivo::query()
                    ->lockForUpdate()
                    ->findOrFail($attributes['dispositivo_id']);

                if ($dispositivo->estado()->where('nombre', '!=', 'Activo')->exists()) {
                    throw ValidationException::withMessages([
                        'dispositivo_id' => 'Solo se pueden asociar dispositivos en estado Activo.',
                    ]);
                }

                if ($evento !== null && (bool) $evento->auto_admin) {
                    throw ValidationException::withMessages([
                        'dispositivo_id' => 'Los eventos auto administrados no permiten asociar un dispositivo concreto.',
                    ]);
                }

                if ($evento !== null && ! $evento->dispositivos()->whereKey($dispositivo->id)->exists()) {
                    throw ValidationException::withMessages([
                        'dispositivo_id' => 'El dispositivo debe estar asignado al evento seleccionado.',
                    ]);
                }

                if ($reserva !== null && $dispositivo->tipo_dispo_id !== $reserva->tipo_dispo_id) {
                    throw ValidationException::withMessages([
                        'dispositivo_id' => 'El dispositivo debe ser del mismo tipo que el reservado.',
                    ]);
                }

                $eventsUsingDevice = Evento::query()
                    ->with('estado:id,nombre')
                    ->whereHas('dispositivos', function (Builder $query) use ($dispositivo): void {
                        $query->whereKey($dispositivo->id);
                    })
                    ->where('inicio', '<=', $now->toDateTimeString())
                    ->whereHas('estado', function (Builder $query): void {
                        $query->where('nombre', 'Activo');
                    })
                    ->get(['id', 'inicio', 'duracion_horas']);
                $currentEventsUsingDevice = $eventsUsingDevice->filter(function (Evento $activeEvent) use ($now): bool {
                    $eventEnd = Carbon::parse($activeEvent->inicio, config('app.timezone'))
                        ->addHours((int) $activeEvent->duracion_horas);

                    return $now->lt($eventEnd);
                });

                if ($evento === null && $currentEventsUsingDevice->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'dispositivo_id' => 'El dispositivo está reservado por un evento en curso y no puede usarse en una asociación general.',
                    ]);
                }

                if ($evento !== null && $currentEventsUsingDevice->contains(
                    fn (Evento $activeEvent): bool => $activeEvent->id !== $evento->id,
                )) {
                    throw ValidationException::withMessages([
                        'dispositivo_id' => 'El dispositivo está bajo la prioridad de otro evento en curso.',
                    ]);
                }
            } elseif ($evento === null || ! (bool) $evento->auto_admin) {
                throw ValidationException::withMessages([
                    'dispositivo_id' => 'Selecciona un dispositivo, o un evento auto administrado sin dispositivo.',
                ]);
            }

            $warnings = [];
            $sharedAssociations = collect();
            if ($dispositivo !== null) {
                $sharedAssociations = Asociacion::query()
                    ->with('usuario:id,nombres,apellidos')
                    ->where('dispositivo_id', $dispositivo->id)
                    ->whereIn('estado_id', $estadoIdsVigentes)
                    ->where('usuario_id', '!=', $usuario->id)
                    ->lockForUpdate()
                    ->get(['id', 'usuario_id', 'inicio', 'final', 'estado_id']);

                if ($sharedAssociations->isNotEmpty() && ! $attributes['confirmar_compartido']) {
                    $sharedPlayers = $sharedAssociations
                        ->map(function (Asociacion $activeAssociation): string {
                            $name = trim(($activeAssociation->usuario?->nombres ?? '').' '.($activeAssociation->usuario?->apellidos ?? ''));
                            $hours = max(
                                0,
                                Carbon::parse($activeAssociation->final)->getTimestamp()
                                    - Carbon::parse($activeAssociation->inicio)->getTimestamp(),
                            ) / 3600;

                            return ($name !== '' ? $name : 'Jugador sin identificar')
                                .' ('.number_format($hours, 1, '.', '').' h)';
                        })
                        ->implode(', ');
                    $warnings['confirmar_compartido'] = 'Este dispositivo ya tiene otros jugadores asociados: '.$sharedPlayers.'. Confirma si el uso compartido es intencional.';
                }

                $reservationConflicts = Reserva::query()
                    ->with('usuario:id,nombres,apellidos')
                    ->where('tipo_dispo_id', $dispositivo->tipo_dispo_id)
                    ->where('usuario_id', '!=', $usuario->id)
                    ->where('evento_id', $evento?->id)
                    ->whereIn('estado_id', $estadoIdsVigentes)
                    ->where('inicio', '<=', $now->toDateTimeString())
                    ->get(['id', 'usuario_id', 'inicio', 'duracion_min']);
                $reservationConflicts = $reservationConflicts->filter(function (Reserva $activeReservation) use ($now): bool {
                    $reservationEnd = Carbon::parse($activeReservation->inicio, config('app.timezone'))
                        ->addMinutes((int) $activeReservation->duracion_min);

                    return $now->lt($reservationEnd);
                });

                if ($reservationConflicts->isNotEmpty() && ! $attributes['confirmar_reserva']) {
                    $reservationOwners = $reservationConflicts
                        ->map(fn (Reserva $activeReservation): string => trim(
                            ($activeReservation->usuario?->nombres ?? '').' '.($activeReservation->usuario?->apellidos ?? ''),
                        ) ?: 'Jugador sin identificar')
                        ->unique()
                        ->implode(', ');
                    $warnings['confirmar_reserva'] = 'Hay una reserva vigente de '.$reservationOwners.'. Su titular puede llegar durante su franja y reclamar un dispositivo; si llega, el administrador deberá cerrar o trasladar esta asociación. Confirma para continuar.';
                }
            }

            if ($warnings !== []) {
                throw ValidationException::withMessages($warnings);
            }

            $reservationsToDelete = $reserva !== null
                ? collect([$reserva])
                : $usuario->reservas()
                    ->whereIn('estado_id', $estadoIdsVigentes)
                    ->whereDate('inicio', $now->toDateString())
                    ->lockForUpdate()
                    ->get();

            if ($reservationsToDelete->isNotEmpty() && $estadoEliminado === null) {
                throw ValidationException::withMessages([
                    'reserva_id' => 'No se encontró el estado Eliminado para resolver la reserva del jugador.',
                ]);
            }

            $asociacionStart = now(config('app.timezone'));
            $asociacion = $usuario->asociaciones()->create([
                'dispositivo_id' => $dispositivo?->id,
                'evento_id' => $evento?->id,
                'reserva_id' => $reserva?->id,
                'estado_id' => $estadoActivo->id,
                'inicio' => $asociacionStart,
                'final' => $asociacionStart,
            ]);

            $mensaje = trim($mensaje ?? '');
            if ($mensaje !== '') {
                Observacion::query()->create([
                    'usuario_id' => $administradorId,
                    'mensaje' => $mensaje,
                    'asociacion_id' => $asociacion->id,
                ]);
            }

            $accion = Accion::query()->where('nombre', 'Creó Asociación')->firstOrFail();
            Auditoria::query()->create([
                'usuario_id' => $administradorId,
                'accion_id' => $accion->id,
                'tabla' => 'asociaciones',
                'indice' => $asociacion->id,
            ]);

            if ($reservationsToDelete->isNotEmpty()) {
                $reservationsToDelete->each(fn (Reserva $activeReservation) => $activeReservation->update([
                    'estado_id' => $estadoEliminado->id,
                ]));
            }

            return $asociacion;
        }, 3);
    }

    public function restoreReservationConversion(int $usuarioId, int $reservaId): void
    {
        DB::transaction(function () use ($usuarioId, $reservaId): void {
            $usuario = Usuario::query()->lockForUpdate()->findOrFail($usuarioId);
            $reserva = $usuario->reservas()
                ->lockForUpdate()
                ->whereHas('estado', function (Builder $query): void {
                    $query->where('nombre', 'Bloqueado');
                })
                ->find($reservaId);

            if ($reserva !== null) {
                $estadoActivo = Estado::query()->where('nombre', 'Activo')->firstOrFail();
                $reserva->update(['estado_id' => $estadoActivo->id]);
            }
        });
    }
}
