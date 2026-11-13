<?php

namespace App\Services;

use App\Models\Dispositivo;
use App\Models\Estado;
use App\Models\Evento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
     *     auto_admin: bool
     * } $attributes
     * @param  array<int, int|string>  $deviceIds
     */
    public function handle(array $attributes, array $deviceIds): Evento
    {
        $this->authorize();

        return DB::transaction(function () use ($attributes, $deviceIds): Evento {
            $activeState = Estado::query()->where('nombre', 'Activo')->firstOrFail();
            $this->validateEvent($attributes, $deviceIds, $activeState->id);
            $this->checkCollisions($attributes, $deviceIds, $activeState->id);

            $event = Evento::query()->create([
                ...$attributes,
                'estado_id' => $activeState->id,
            ]);

            $event->dispositivos()->sync($deviceIds);

            return $event;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int|string>  $deviceIds
     */
    public function update(Evento $event, array $attributes, array $deviceIds): void
    {
        $this->authorize();

        DB::transaction(function () use ($event, $attributes, $deviceIds): void {
            $activeState = Estado::query()->where('nombre', 'Activo')->firstOrFail();
            $this->validateEvent($attributes, $deviceIds, $activeState->id);
            $this->checkCollisions($attributes, $deviceIds, $activeState->id, $event->id);

            $event = Evento::query()->lockForUpdate()->findOrFail($event->id);
            $event->update($attributes);
            $event->dispositivos()->sync($deviceIds);
        }, 3);
    }

    private function authorize(): void
    {
        abort_unless(in_array(auth()->user()?->usuario?->rol?->nombre, ['Administrador', 'Master'], true), 403);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int|string>  $deviceIds
     */
    private function validateEvent(array $attributes, array $deviceIds, int $activeStateId): void
    {
        Validator::make([...$attributes, 'dispositivos' => $deviceIds], [
            'usuario_id' => ['required', 'integer', Rule::exists('usuarios', 'id')->where('estado_id', $activeStateId)],
            'inicio' => ['required', 'date', 'after:now'],
            'duracion_horas' => ['required', 'integer', 'min:1'],
            'aforo' => ['required', 'integer', 'min:1'],
            'auto_admin' => ['required', 'boolean'],
            'dispositivos' => ['required', 'array', 'min:1'],
            'dispositivos.*' => ['required', 'integer', 'distinct'],
        ], [
            'usuario_id.exists' => 'El organizador debe estar activo.',
            'inicio.after' => 'La fecha de inicio debe ser posterior al momento actual.',
            'dispositivos.required' => 'Selecciona al menos un dispositivo.',
            'dispositivos.min' => 'Selecciona al menos un dispositivo.',
            'dispositivos.*.distinct' => 'No puedes seleccionar un dispositivo más de una vez.',
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int|string>  $deviceIds
     */
    private function checkCollisions(array $attributes, array $deviceIds, int $activeStateId, ?int $eventId = null): void
    {
        $devices = Dispositivo::query()
            ->whereIn('id', $deviceIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'nombre', 'estado_id']);

        if ($devices->count() !== count($deviceIds) || $devices->contains(fn (Dispositivo $device): bool => (int) $device->estado_id !== $activeStateId)) {
            throw ValidationException::withMessages([
                'dispositivos' => 'Todos los dispositivos seleccionados deben existir y estar activos.',
            ]);
        }

        if (isset($attributes['estado_id']) && (int) $attributes['estado_id'] !== $activeStateId) {
            return;
        }

        $start = Carbon::parse($attributes['inicio']);
        $end = $start->copy()->addHours((int) $attributes['duracion_horas']);
        $events = DB::table('eventos')
            ->join('evento_dispos', 'evento_dispos.evento_id', '=', 'eventos.id')
            ->where('eventos.estado_id', $activeStateId)
            ->where('eventos.inicio', '<', $end->toDateTimeString())
            ->whereIn('evento_dispos.dispositivo_id', $deviceIds)
            ->when($eventId !== null, fn ($query) => $query->where('eventos.id', '!=', $eventId))
            ->lockForUpdate()
            ->get(['eventos.inicio', 'eventos.duracion_horas', 'evento_dispos.dispositivo_id']);

        $conflictingIds = $events
            ->filter(fn ($existing): bool => Carbon::parse($existing->inicio)->addHours((int) $existing->duracion_horas)->gt($start))
            ->pluck('dispositivo_id')
            ->unique();

        if ($conflictingIds->isNotEmpty()) {
            $names = $devices->whereIn('id', $conflictingIds)->pluck('nombre')->implode(', ');

            throw ValidationException::withMessages([
                'dispositivos' => 'Los siguientes dispositivos tienen un conflicto de horario: '.$names.'.',
            ]);
        }
    }
}
