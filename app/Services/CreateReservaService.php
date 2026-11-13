<?php

namespace App\Services;

use App\Models\Accion;
use App\Models\Asociacion;
use App\Models\Auditoria;
use App\Models\Dispositivo;
use App\Models\Estado;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
    public function handle(int $usuarioId, int $administradorId, array $attributes): Reserva
    {
        $timezone = config('app.timezone');
        $attributes['duracion_min'] = (int) $attributes['duracion_min'];
        $inicio = Carbon::parse($attributes['inicio'], $timezone);
        $fin = $inicio->copy()->addMinutes($attributes['duracion_min']);

        if ($inicio->lt(now())) {
            throw ValidationException::withMessages([
                'fecha_reserva' => 'La fecha y hora de inicio deben ser actuales o futuras.',
            ]);
        }

        return DB::transaction(function () use ($usuarioId, $administradorId, $attributes, $inicio, $fin, $timezone): Reserva {
            $usuario = Usuario::query()->lockForUpdate()->findOrFail($usuarioId);
            $now = now($timezone);

            if ($inicio->lt($now)) {
                throw ValidationException::withMessages([
                    'fecha_reserva' => 'La fecha y hora de inicio deben ser actuales o futuras.',
                ]);
            }

            $estados = Estado::query()
                ->whereIn('nombre', ['Activo', 'Bloqueado'])
                ->get(['id', 'nombre'])
                ->keyBy('nombre');
            $estadoActivo = $estados->get('Activo');

            if ($estadoActivo === null || ! $usuario->estado()->where('nombre', 'Activo')->exists()) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'Solo los jugadores activos pueden crear reservas.',
                ]);
            }

            $estadoIdsVigentes = $estados->pluck('id');
            $hasCurrentReservation = $usuario->reservas()
                ->whereIn('estado_id', $estadoIdsVigentes)
                ->exists();

            if ($hasCurrentReservation) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'El jugador ya tiene una reserva activa o bloqueada que debe resolver antes de crear otra.',
                ]);
            }

            $hasCurrentAssociation = $usuario->asociaciones()
                ->whereHas('estado', function (Builder $query): void {
                    $query->whereIn('nombre', ['Activo', 'Bloqueado']);
                })
                ->exists();

            if ($hasCurrentAssociation && $inicio->toDateString() === $now->toDateString()) {
                throw ValidationException::withMessages([
                    'fecha_reserva' => 'El jugador tiene una asociación vigente y no puede reservar para el día de hoy.',
                ]);
            }

            $eventoId = $attributes['evento_id'];
            $evento = null;

            if ($eventoId !== null) {
                $evento = Evento::query()
                    ->with('estado:id,nombre')
                    ->findOrFail($eventoId);
                $inicioEvento = Carbon::parse($evento->inicio, $timezone);
                $finEvento = $inicioEvento->copy()->addHours((int) $evento->duracion_horas);

                if ($evento->estado?->nombre !== 'Activo') {
                    throw ValidationException::withMessages([
                        'evento_id' => 'Solo se pueden reservar dispositivos para eventos activos.',
                    ]);
                }

                if ($evento->auto_admin) {
                    throw ValidationException::withMessages([
                        'evento_id' => 'Este evento está auto administrado. Solo los organizadores pueden gestionar las reservas y asociaciones para este evento.',
                    ]);
                }

                if ($inicio->lt($inicioEvento) || $inicio->gte($finEvento)) {
                    throw ValidationException::withMessages([
                        'fecha_reserva' => 'El inicio de la reserva debe estar dentro del horario del evento.',
                    ]);
                }
            }

            $devices = Dispositivo::query()
                ->where('tipo_dispo_id', $attributes['tipo_dispo_id'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'estado_id']);
            $activeDeviceIds = $devices
                ->where('estado_id', $estadoActivo->id)
                ->pluck('id')
                ->map(fn (int $id): int => $id)
                ->all();

            if ($activeDeviceIds === []) {
                throw ValidationException::withMessages([
                    'tipo_dispo_id' => 'No hay dispositivos activos de este tipo.',
                ]);
            }

            if ($evento !== null) {
                $candidateDeviceIds = $evento->dispositivos()
                    ->whereIn('dispositivos.id', $activeDeviceIds)
                    ->pluck('dispositivos.id')
                    ->map(fn (int $id): int => $id)
                    ->all();

                if ($candidateDeviceIds === []) {
                    throw ValidationException::withMessages([
                        'evento_id' => 'El evento no tiene dispositivos activos de este tipo.',
                    ]);
                }
            } else {
                $candidateDeviceIds = $activeDeviceIds;
            }

            $activeEvents = Evento::query()
                ->with('dispositivos:id,tipo_dispo_id')
                ->where('inicio', '<', $fin->toDateTimeString())
                ->whereHas('estado', function (Builder $query): void {
                    $query->where('nombre', 'Activo');
                })
                ->get(['id', 'inicio', 'duracion_horas']);
            $eventOccupiedDeviceIds = [];

            foreach ($activeEvents as $activeEvent) {
                $eventStart = Carbon::parse($activeEvent->inicio, $timezone);
                $eventEnd = $eventStart->copy()->addHours((int) $activeEvent->duracion_horas);

                if (! $this->intervalsOverlap($inicio, $fin, $eventStart, $eventEnd)) {
                    continue;
                }

                if ($evento !== null && $activeEvent->id === $evento->id) {
                    continue;
                }

                $eventOccupiedDeviceIds = [
                    ...$eventOccupiedDeviceIds,
                    ...$activeEvent->dispositivos
                        ->whereIn('id', $candidateDeviceIds)
                        ->pluck('id')
                        ->map(fn (int $id): int => $id)
                        ->all(),
                ];
            }

            $candidateDeviceIds = array_values(array_diff(
                $candidateDeviceIds,
                array_unique($eventOccupiedDeviceIds),
            ));

            if ($candidateDeviceIds === []) {
                throw ValidationException::withMessages([
                    'tipo_dispo_id' => $evento === null
                        ? 'Los eventos activos ocupan los dispositivos de este tipo durante la franja solicitada.'
                        : 'Otro evento activo ocupa los dispositivos asignados a este evento durante la franja solicitada.',
                ]);
            }

            $reservations = Reserva::query()
                ->where('tipo_dispo_id', $attributes['tipo_dispo_id'])
                ->whereIn('estado_id', $estadoIdsVigentes)
                ->where('inicio', '<', $fin->toDateTimeString())
                ->when(
                    $eventoId === null,
                    fn (Builder $query): Builder => $query->whereNull('evento_id'),
                    fn (Builder $query): Builder => $query->where('evento_id', $eventoId),
                )
                ->get(['id', 'inicio', 'duracion_min']);
            $reservationIntervals = [];

            foreach ($reservations as $reservation) {
                $reservationStart = Carbon::parse($reservation->inicio, $timezone);
                $reservationEnd = $reservationStart->copy()->addMinutes((int) $reservation->duracion_min);

                if ($this->intervalsOverlap($inicio, $fin, $reservationStart, $reservationEnd)) {
                    $reservationIntervals[] = [
                        'start' => $reservationStart->getTimestamp(),
                        'end' => $reservationEnd->getTimestamp(),
                    ];
                }
            }

            $associations = Asociacion::query()
                ->with('reserva:id,inicio,duracion_min')
                ->whereNotNull('dispositivo_id')
                ->whereIn('dispositivo_id', $candidateDeviceIds)
                ->whereIn('estado_id', $estadoIdsVigentes)
                ->when(
                    $eventoId === null,
                    fn (Builder $query): Builder => $query->whereNull('evento_id'),
                    fn (Builder $query): Builder => $query->where('evento_id', $eventoId),
                )
                ->get(['id', 'dispositivo_id', 'estado_id', 'reserva_id', 'inicio', 'final']);
            $associationIntervals = [];
            foreach ($associations as $association) {
                $referenceStart = Carbon::parse(
                    $association->reserva?->inicio ?? $association->inicio,
                    $timezone,
                );
                $thresholdMinutes = max(30, (int) ($association->reserva?->duracion_min ?? 0));
                $final = Carbon::parse($association->final, $timezone);
                $elapsedSeconds = max(0, $final->getTimestamp() - $referenceStart->getTimestamp());
                $thresholdSeconds = $thresholdMinutes * 60;

                if ($elapsedSeconds >= $thresholdSeconds) {
                    continue;
                }

                $blockStart = max(
                    $now->getTimestamp(),
                    $inicio->getTimestamp(),
                    Carbon::parse($association->inicio, $timezone)->getTimestamp(),
                );
                $blockEnd = $fin->getTimestamp();

                if ($association->estado_id === $estadoActivo->id) {
                    $remainingSeconds = $thresholdSeconds - $elapsedSeconds;
                    $projectionStart = $final->greaterThan($now) ? $final->copy() : $now->copy();
                    $blockEnd = min(
                        $blockEnd,
                        $projectionStart->addSeconds($remainingSeconds)->getTimestamp(),
                    );
                }

                if ($blockStart < $blockEnd) {
                    $associationIntervals[] = [
                        'device_id' => $association->dispositivo_id,
                        'start' => $blockStart,
                        'end' => $blockEnd,
                    ];
                }
            }

            $this->ensureContinuousAvailability(
                $candidateDeviceIds,
                $reservationIntervals,
                $associationIntervals,
                $inicio,
                $fin,
            );

            $reserva = $usuario->reservas()->create([
                ...$attributes,
                'estado_id' => $estadoActivo->id,
            ]);
            $accion = Accion::query()->where('nombre', 'Creó Reserva')->firstOrFail();

            Auditoria::query()->create([
                'usuario_id' => $administradorId,
                'accion_id' => $accion->id,
                'tabla' => 'reservas',
                'indice' => $reserva->id,
            ]);

            return $reserva;
        }, 3);
    }

    private function intervalsOverlap(Carbon $firstStart, Carbon $firstEnd, Carbon $secondStart, Carbon $secondEnd): bool
    {
        return $firstStart->lt($secondEnd) && $secondStart->lt($firstEnd);
    }

    /**
     * @param  array<int, int>  $candidateDeviceIds
     * @param  array<int, array{start: int, end: int}>  $reservationIntervals
     * @param  array<int, array{device_id: int, start: int, end: int}>  $associationIntervals
     */
    private function ensureContinuousAvailability(
        array $candidateDeviceIds,
        array $reservationIntervals,
        array $associationIntervals,
        Carbon $inicio,
        Carbon $fin,
    ): void {
        $start = $inicio->getTimestamp();
        $end = $fin->getTimestamp();
        $boundaries = [$start, $end];

        foreach ([...$reservationIntervals, ...$associationIntervals] as $interval) {
            if ($interval['start'] > $start && $interval['start'] < $end) {
                $boundaries[] = $interval['start'];
            }

            if ($interval['end'] > $start && $interval['end'] < $end) {
                $boundaries[] = $interval['end'];
            }
        }

        $boundaries = array_values(array_unique($boundaries));
        sort($boundaries);
        $availableForWholeSlot = $candidateDeviceIds;
        $blockedByReservation = false;
        $blockedByAssociation = false;

        for ($index = 0, $last = count($boundaries) - 1; $index < $last; $index++) {
            $segmentStart = $boundaries[$index];
            $reservationCount = count(array_filter(
                $reservationIntervals,
                fn (array $interval): bool => $interval['start'] <= $segmentStart && $interval['end'] > $segmentStart,
            ));
            $blockedDeviceIds = array_values(array_unique(array_column(array_filter(
                $associationIntervals,
                fn (array $interval): bool => $interval['start'] <= $segmentStart && $interval['end'] > $segmentStart,
            ), 'device_id')));
            $freeDeviceIds = array_values(array_diff($candidateDeviceIds, $blockedDeviceIds));

            if (count($freeDeviceIds) <= $reservationCount) {
                $blockedByReservation = $blockedByReservation || $reservationCount > 0;
                $blockedByAssociation = $blockedByAssociation || $blockedDeviceIds !== [];
                $this->throwUnavailableCapacity($blockedByReservation, $blockedByAssociation);
            }

            $availableForWholeSlot = array_values(array_intersect($availableForWholeSlot, $freeDeviceIds));
        }

        if ($availableForWholeSlot === []) {
            throw ValidationException::withMessages([
                'tipo_dispo_id' => 'Ningún dispositivo mantiene disponibilidad continua durante toda la franja solicitada.',
            ]);
        }
    }

    private function throwUnavailableCapacity(bool $blockedByReservation, bool $blockedByAssociation): never
    {
        $message = match (true) {
            $blockedByReservation && $blockedByAssociation => 'Las reservas y asociaciones vigentes consumen la capacidad disponible durante parte de la franja solicitada.',
            $blockedByReservation => 'Las reservas existentes consumen la capacidad disponible durante parte de la franja solicitada.',
            $blockedByAssociation => 'Las asociaciones que aún consumen capacidad ocupan los dispositivos necesarios durante parte de la franja solicitada.',
            default => 'No hay capacidad disponible durante toda la franja solicitada.',
        };

        throw ValidationException::withMessages([
            'tipo_dispo_id' => $message,
        ]);
    }
}
