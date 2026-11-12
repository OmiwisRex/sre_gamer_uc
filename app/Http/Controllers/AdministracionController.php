<?php

namespace App\Http\Controllers;

use App\Models\Asociacion;
use App\Models\Dispositivo;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\TipoDispo;
use App\Models\Usuario;
use App\Services\CreateAsociacionService;
use App\Services\CreateReservaService;
use App\Services\ManagePlayerActivityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdministracionController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'jugador_id' => ['nullable', 'string', 'max:24'],
        ]);
        $identificacionJugador = trim($validated['jugador_id'] ?? '');
        $jugador = null;
        $reservaActiva = null;
        $asociacionActiva = null;
        $historialAsociaciones = collect();
        $sharedAssociations = collect();
        $reservaIdConversion = null;
        $now = now(config('app.timezone'));

        if ($identificacionJugador !== '') {
            $jugador = Usuario::query()
                ->with([
                    'estado:id,nombre',
                    'rol:id,nombre',
                    'sesion:id,usuario_id,email',
                ])
                ->where(function (Builder $query) use ($identificacionJugador): void {
                    $query->where('cedula', $identificacionJugador)
                        ->orWhere('codigo', $identificacionJugador);
                })
                ->first();

            if ($jugador) {
                $reservaActiva = Reserva::query()
                    ->with(['estado:id,nombre', 'tipoDispo:id,nombre', 'evento:id,nombre'])
                    ->where('usuario_id', $jugador->id)
                    ->whereHas('estado', function (Builder $query): void {
                        $query->whereIn('nombre', ['Activo', 'Bloqueado']);
                    })
                    ->orderByDesc('inicio')
                    ->orderByDesc('id')
                    ->first();

                $asociacionActiva = Asociacion::query()
                    ->with([
                        'estado:id,nombre',
                        'dispositivo:id,nombre',
                        'evento:id,nombre',
                        'observaciones' => function (HasMany $query): void {
                            $query->orderBy('created_at')->orderBy('id');
                        },
                    ])
                    ->where('usuario_id', $jugador->id)
                    ->whereHas('estado', function (Builder $query): void {
                        $query->whereIn('nombre', ['Activo', 'Bloqueado']);
                    })
                    ->orderByDesc('inicio')
                    ->orderByDesc('id')
                    ->first();

                if ($reservaActiva?->estado?->nombre === 'Bloqueado') {
                    $reservaIdConversion = $reservaActiva->id;
                }

                $historialAsociaciones = $this->dailyAssociationHours($jugador);
            }
        }

        $associationEvents = Evento::query()
            ->with('dispositivos:id')
            ->where('inicio', '<=', $now->toDateTimeString())
            ->whereHas('estado', function (Builder $query): void {
                $query->where('nombre', 'Activo');
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'inicio', 'duracion_horas', 'auto_admin'])
            ->filter(function (Evento $evento) use ($now): bool {
                return $now->lt(
                    Carbon::parse($evento->inicio, config('app.timezone'))
                        ->addHours((int) $evento->duracion_horas),
                );
            })
            ->values();

        $activeDevices = Dispositivo::query()
            ->with(['tipoDispo:id,nombre', 'eventos:id,nombre'])
            ->whereHas('estado', function (Builder $query): void {
                $query->where('nombre', 'Activo');
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo_dispo_id', 'estado_id']);

        $devicesToShowSharing = $activeDevices->modelKeys();
        if ($asociacionActiva?->dispositivo_id !== null) {
            $devicesToShowSharing[] = $asociacionActiva->dispositivo_id;
        }
        $devicesToShowSharing = array_values(array_unique($devicesToShowSharing));

        if ($jugador !== null && $devicesToShowSharing !== []) {
            $sharedAssociations = Asociacion::query()
                ->with(['usuario:id,nombres,apellidos', 'dispositivo:id,nombre'])
                ->whereIn('dispositivo_id', $devicesToShowSharing)
                ->whereIn('estado_id', function ($query): void {
                    $query->select('id')
                        ->from('estados')
                        ->whereIn('nombre', ['Activo', 'Bloqueado']);
                })
                ->where('usuario_id', '!=', $jugador->id)
                ->orderBy('inicio')
                ->get(['id', 'usuario_id', 'dispositivo_id', 'inicio', 'final']);

            $sharedAssociations->each(function (Asociacion $asociacion): void {
                $start = Carbon::parse($asociacion->inicio, config('app.timezone'));
                $end = Carbon::parse($asociacion->final, config('app.timezone'));
                $asociacion->setAttribute(
                    'horas',
                    max(0, $end->getTimestamp() - $start->getTimestamp()) / 3600,
                );
            });
        }

        $associationDevices = $activeDevices;
        if ($reservaIdConversion !== null && $reservaActiva !== null) {
            $associationDevices = $activeDevices
                ->where('tipo_dispo_id', $reservaActiva->tipo_dispo_id)
                ->values();

            if ($reservaActiva->evento_id !== null) {
                $associationDevices = $associationDevices
                    ->filter(fn (Dispositivo $dispositivo): bool => $dispositivo->eventos->contains('id', $reservaActiva->evento_id))
                    ->values();
            } else {
                $busyDeviceIds = $associationEvents
                    ->flatMap(fn (Evento $evento) => $evento->dispositivos->modelKeys())
                    ->unique()
                    ->all();
                $associationDevices = $associationDevices->whereNotIn('id', $busyDeviceIds)->values();
            }
        }

        $fechaSeleccionada = now(config('app.timezone'))->toDateString();
        $scheduleData = $this->getScheduleData($fechaSeleccionada);

        return view('administracion.index', [
            'tiposDispo' => TipoDispo::query()->orderBy('nombre')->get(['id', 'nombre']),
            'eventos' => Evento::query()
                ->where('auto_admin', false)
                ->whereHas('estado', function (Builder $query): void {
                    $query->where('nombre', 'Activo');
                })
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'eventosAsociacion' => $associationEvents,
            'dispositivos' => $associationDevices,
            'asociacionesCompartidas' => $sharedAssociations,
            'jugador' => $jugador,
            'jugadorId' => $identificacionJugador,
            'reservaActiva' => $reservaActiva,
            'asociacionActiva' => $asociacionActiva,
            'historialAsociaciones' => $historialAsociaciones,
            'totalHoras' => $historialAsociaciones->sum('horas'),
            'hoursRefreshUrl' => $asociacionActiva?->estado?->nombre === 'Activo'
                ? route('administracion.jugadores.horas.refresh', $jugador)
                : null,
            'reserva_id' => $reservaIdConversion,
            'fechaSeleccionada' => $fechaSeleccionada,
            'horasTimeline' => range(0, 23),
            'ocupaciones' => $scheduleData['ocupaciones'],
            'seleccionItems' => $scheduleData['seleccionItems'],
        ]);
    }

    public function refreshPlayerHours(
        Usuario $usuario,
        ManagePlayerActivityService $managePlayerActivity,
    ): View {
        $managePlayerActivity->syncActiveAssociationEndTimesForUser($usuario);
        $historialAsociaciones = $this->dailyAssociationHours($usuario);
        $hasActiveAssociation = $usuario->asociaciones()
            ->whereHas('estado', function (Builder $query): void {
                $query->where('nombre', 'Activo');
            })
            ->exists();

        return view('administracion.comp_horas', [
            'jugador' => $usuario,
            'historialAsociaciones' => $historialAsociaciones,
            'totalHoras' => $historialAsociaciones->sum('horas'),
            'hoursRefreshUrl' => $hasActiveAssociation
                ? route('administracion.jugadores.horas.refresh', $usuario)
                : null,
        ]);
    }

    public function getSchedule(Request $request)
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        $scheduleData = $this->getScheduleData($validated['fecha']);

        $dispositivos = Dispositivo::query()
            ->with(['tipoDispo:id,nombre', 'estado:id,nombre'])
            ->whereHas('estado', function (Builder $query): void {
                $query->where('nombre', 'Activo');
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo_dispo_id', 'estado_id']);

        return response()->json([
            'ocupaciones' => $scheduleData['ocupaciones'],
            'dispositivos' => $dispositivos,
            'horasTimeline' => range(0, 23),
        ]);
    }

    public function getScheduleSelection(Request $request)
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'dispositivo_id' => ['required', 'integer'],
            'hora' => ['required', 'integer', 'min:0', 'max:23'],
        ]);

        $fecha = Carbon::createFromFormat('Y-m-d', $validated['fecha'], config('app.timezone'))->startOfDay();
        $horaInicio = $fecha->copy()->setHour($validated['hora'])->setMinute(0)->setSecond(0);
        $horaFin = $horaInicio->copy()->addHour();

        $dispositivo = Dispositivo::query()->find($validated['dispositivo_id']);
        if (! $dispositivo) {
            return response()->json(['items' => []]);
        }

        $items = collect();

        if ($dispositivo->tipoDispo) {
            $reservas = Reserva::query()
                ->with(['usuario:id,nombres,apellidos', 'tipoDispo:id,nombre', 'evento:id,nombre'])
                ->where('tipo_dispo_id', $dispositivo->tipo_dispo_id)
                ->where(function (Builder $query) use ($horaInicio, $horaFin): void {
                    $query->where('inicio', '<', $horaFin)
                        ->whereRaw('DATE_ADD(inicio, INTERVAL duracion_min MINUTE) > ?', [$horaInicio]);
                })
                ->get();

            foreach ($reservas as $reserva) {
                $items->push([
                    'tipo' => $reserva->evento_id ? 'reservation-event' : 'reservation',
                    'nombre' => trim($reserva->usuario->nombres . ' ' . $reserva->usuario->apellidos),
                    'inicio' => $reserva->inicio,
                    'duracion_min' => $reserva->duracion_min,
                    'evento' => $reserva->evento?->nombre,
                    'usuario_id' => $reserva->usuario_id,
                ]);
            }
        }

        $asociaciones = Asociacion::query()
            ->with(['usuario:id,nombres,apellidos', 'dispositivo:id,nombre', 'evento:id,nombre'])
            ->where('dispositivo_id', $dispositivo->id)
            ->where(function (Builder $query) use ($horaInicio, $horaFin): void {
                $query->where('inicio', '<', $horaFin)
                    ->where('final', '>', $horaInicio);
            })
            ->get();

        foreach ($asociaciones as $asociacion) {
            $items->push([
                'tipo' => $asociacion->evento_id ? 'association-event' : 'association',
                'nombre' => trim($asociacion->usuario->nombres . ' ' . $asociacion->usuario->apellidos),
                'inicio' => $asociacion->inicio,
                'duracion_min' => (int) ((strtotime($asociacion->final) - strtotime($asociacion->inicio)) / 60),
                'evento' => $asociacion->evento?->nombre,
                'usuario_id' => $asociacion->usuario_id,
            ]);
        }

        return response()->json(['items' => $items]);
    }

    public function storeReservation(Request $request, CreateReservaService $createReserva): RedirectResponse
    {
        $validated = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'tipo_dispo_id' => ['required', 'integer', 'exists:tipo_dispos,id'],
            'evento_id' => ['nullable', 'integer', 'exists:eventos,id'],
            'fecha_reserva' => ['required', 'date_format:Y-m-d'],
            'hora_reserva' => ['required', 'date_format:H:i'],
            'duracion_min' => ['required', 'integer', 'in:30,60,90,120,150,180'],
        ]);
        $inicio = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['fecha_reserva'].' '.$validated['hora_reserva'],
        );

        if ($inicio->lt(now())) {
            return back()
                ->withErrors(['fecha_reserva' => 'La fecha y hora de inicio deben ser actuales o futuras.'])
                ->withInput();
        }

        $jugador = Usuario::query()->findOrFail($validated['usuario_id']);
        $administradorId = $request->user()->usuario_id;

        if ($administradorId === null) {
            abort(403, 'La sesión no está vinculada a un usuario administrador.');
        }

        $createReserva->handle(
            $jugador->id,
            (int) $administradorId,
            [
                'tipo_dispo_id' => $validated['tipo_dispo_id'],
                'evento_id' => $validated['evento_id'] ?? null,
                'inicio' => $inicio->toDateTimeString(),
                'duracion_min' => $validated['duracion_min'],
            ],
        );

        return redirect()
            ->route('administracion.index', ['jugador_id' => $jugador->cedula])
            ->with('success', 'Reserva creada correctamente.');
    }

    public function storeAssociation(Request $request, CreateAsociacionService $createAsociacion): RedirectResponse
    {
        $usuarioId = filter_var($request->input('usuario_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $reservaId = filter_var($request->input('reserva_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        try {
            $validated = $request->validate([
                'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
                'dispositivo_id' => ['nullable', 'integer', 'exists:dispositivos,id'],
                'evento_id' => ['nullable', 'integer', 'exists:eventos,id'],
                'reserva_id' => ['nullable', 'integer', 'exists:reservas,id'],
                'observacion' => ['nullable', 'string', 'max:256'],
                'confirmar_compartido' => ['sometimes', 'boolean'],
                'confirmar_reserva' => ['sometimes', 'boolean'],
            ]);
            $jugador = Usuario::query()->findOrFail($validated['usuario_id']);

            $createAsociacion->handle(
                $jugador->id,
                $this->administratorId($request),
                [
                    'dispositivo_id' => isset($validated['dispositivo_id']) ? (int) $validated['dispositivo_id'] : null,
                    'evento_id' => isset($validated['evento_id']) ? (int) $validated['evento_id'] : null,
                    'reserva_id' => isset($validated['reserva_id']) ? (int) $validated['reserva_id'] : null,
                    'confirmar_compartido' => (bool) ($validated['confirmar_compartido'] ?? false),
                    'confirmar_reserva' => (bool) ($validated['confirmar_reserva'] ?? false),
                ],
                $validated['observacion'] ?? null,
            );
        } catch (ValidationException $exception) {
            $warningKeys = ['confirmar_compartido', 'confirmar_reserva'];
            $isConfirmation = array_intersect($warningKeys, array_keys($exception->errors())) !== [];

            if ($reservaId && $usuarioId && ! $isConfirmation) {
                $createAsociacion->restoreReservationConversion((int) $usuarioId, (int) $reservaId);
            }

            throw $exception;
        }

        return redirect()
            ->route('administracion.index', ['jugador_id' => $jugador->cedula])
            ->with('success', 'Asociación creada correctamente.');
    }

    public function beginReservationConversion(
        Reserva $reserva,
        ManagePlayerActivityService $managePlayerActivity,
    ): RedirectResponse {
        $reserva = $managePlayerActivity->beginReservationConversion($reserva->id);
        $reserva->loadMissing('usuario:id,cedula');

        return redirect()->route('administracion.index', [
            'jugador_id' => $reserva->usuario->cedula,
            'reserva_id' => $reserva->id,
        ]);
    }

    public function cancelReservation(
        Reserva $reserva,
        ManagePlayerActivityService $managePlayerActivity,
    ): RedirectResponse {
        $reserva = $managePlayerActivity->cancelReservation($reserva->id);
        $reserva->loadMissing('usuario:id,cedula');

        return redirect()
            ->route('administracion.index', ['jugador_id' => $reserva->usuario->cedula])
            ->with('success', 'Reserva cancelada correctamente.');
    }

    public function cancelReservationConversion(
        Reserva $reserva,
        ManagePlayerActivityService $managePlayerActivity,
    ): RedirectResponse {
        $reserva = $managePlayerActivity->cancelReservationConversion($reserva->id);
        $reserva->loadMissing('usuario:id,cedula');

        return redirect()
            ->route('administracion.index', ['jugador_id' => $reserva->usuario->cedula])
            ->with('success', 'Conversión cancelada; la reserva volvió al estado Activo.');
    }

    public function closeAssociation(
        Asociacion $asociacion,
        ManagePlayerActivityService $managePlayerActivity,
    ): RedirectResponse {
        $asociacion = $managePlayerActivity->closeAssociation($asociacion->id);
        $asociacion->loadMissing('usuario:id,cedula');

        return redirect()
            ->route('administracion.index', ['jugador_id' => $asociacion->usuario->cedula])
            ->with('success', 'Asociación cerrada correctamente.');
    }

    public function continueAssociation(
        Asociacion $asociacion,
        ManagePlayerActivityService $managePlayerActivity,
    ): RedirectResponse {
        $asociacion = $managePlayerActivity->continueAssociation($asociacion->id);
        $asociacion->loadMissing('usuario:id,cedula');

        return redirect()
            ->route('administracion.index', ['jugador_id' => $asociacion->usuario->cedula])
            ->with('success', 'Asociación continuada correctamente.');
    }

    private function administratorId(Request $request): int
    {
        $administratorId = $request->user()->usuario_id;

        if ($administratorId === null) {
            abort(403, 'La sesión no está vinculada a un usuario administrador.');
        }

        return (int) $administratorId;
    }

    private function dailyAssociationHours(Usuario $jugador): Collection
    {
        $historialAsociaciones = Asociacion::query()
            ->with('dispositivo:id,nombre')
            ->where('usuario_id', $jugador->id)
            ->whereDate('inicio', today())
            ->orderByDesc('inicio')
            ->orderByDesc('id')
            ->get();

        $historialAsociaciones->each(function (Asociacion $asociacion): void {
            $inicio = Carbon::parse($asociacion->inicio, config('app.timezone'));
            $final = Carbon::parse($asociacion->final, config('app.timezone'));
            $asociacion->setAttribute(
                'horas',
                max(0, $final->getTimestamp() - $inicio->getTimestamp()) / 3600,
            );
        });

        return $historialAsociaciones;
    }

    private function getScheduleData(string $fechaSeleccionada): array
    {
        $fecha = Carbon::createFromFormat('Y-m-d', $fechaSeleccionada, config('app.timezone'))->startOfDay();
        $fechaFin = $fecha->copy()->endOfDay();

        $activeDevices = Dispositivo::query()
            ->with(['tipoDispo:id,nombre', 'estado:id,nombre'])
            ->whereHas('estado', function (Builder $query): void {
                $query->where('nombre', 'Activo');
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo_dispo_id', 'estado_id']);

        $ocupaciones = [];
        $seleccionItems = collect();

        foreach ($activeDevices as $dispositivo) {
            $ocupaciones[$dispositivo->id] = [];

            for ($hora = 0; $hora < 24; $hora++) {
                $ocupaciones[$dispositivo->id][$hora] = null;
            }
        }

        $asociaciones = Asociacion::query()
            ->with(['usuario:id,nombres,apellidos', 'dispositivo:id,nombre', 'estado:id,nombre', 'evento:id,nombre'])
            ->whereIn('dispositivo_id', $activeDevices->modelKeys())
            ->where(function (Builder $query) use ($fecha, $fechaFin): void {
                $query->whereBetween('inicio', [$fecha, $fechaFin])
                    ->orWhereBetween('final', [$fecha, $fechaFin])
                    ->orWhere(function (Builder $q) use ($fecha, $fechaFin): void {
                        $q->where('inicio', '<', $fecha)->where('final', '>', $fechaFin);
                    });
            })
            ->get();

        foreach ($asociaciones as $asociacion) {
            $inicio = Carbon::parse($asociacion->inicio, config('app.timezone'));
            $final = Carbon::parse($asociacion->final, config('app.timezone'));

            for ($hora = 0; $hora < 24; $hora++) {
                $horaInicio = $fecha->copy()->setHour($hora)->setMinute(0)->setSecond(0);
                $horaFin = $horaInicio->copy()->addHour();

                if ($inicio->lt($horaFin) && $final->gt($horaInicio)) {
                    $tipoAsociacion = $asociacion->evento_id ? 'association-event' : 'association';
                    $ocupaciones[$asociacion->dispositivo_id][$hora] = [
                        'tipo' => $tipoAsociacion,
                        'etiqueta' => $asociacion->usuario->nombres ?? 'Usuario',
                        'id' => $asociacion->id,
                        'usuarioId' => $asociacion->usuario_id,
                        'estado' => $asociacion->estado->nombre,
                    ];
                }
            }
        }

        $reservas = Reserva::query()
            ->with(['usuario:id,nombres,apellidos', 'tipoDispo:id,nombre', 'estado:id,nombre', 'evento:id,nombre'])
            ->where(function (Builder $query) use ($fecha, $fechaFin): void {
                $query->whereBetween('inicio', [$fecha, $fechaFin])
                    ->orWhere(function (Builder $q) use ($fecha, $fechaFin): void {
                        $q->where('inicio', '<', $fechaFin)
                            ->whereRaw('DATE_ADD(inicio, INTERVAL duracion_min MINUTE) > ?', [$fecha]);
                    });
            })
            ->get();

        foreach ($reservas as $reserva) {
            $inicio = Carbon::parse($reserva->inicio, config('app.timezone'));
            $final = $inicio->copy()->addMinutes($reserva->duracion_min);

            $tiposDispoIds = $activeDevices
                ->where('tipo_dispo_id', $reserva->tipo_dispo_id)
                ->modelKeys();

            foreach ($tiposDispoIds as $dispoId) {
                for ($hora = 0; $hora < 24; $hora++) {
                    $horaInicio = $fecha->copy()->setHour($hora)->setMinute(0)->setSecond(0);
                    $horaFin = $horaInicio->copy()->addHour();

                    if ($inicio->lt($horaFin) && $final->gt($horaInicio)) {
                        if (! isset($ocupaciones[$dispoId][$hora]) || $ocupaciones[$dispoId][$hora] === null) {
                            $tipoReserva = $reserva->evento_id ? 'reservation-event' : 'reservation';
                            $ocupaciones[$dispoId][$hora] = [
                                'tipo' => $tipoReserva,
                                'etiqueta' => $reserva->usuario->nombres ?? 'Usuario',
                                'id' => $reserva->id,
                                'usuarioId' => $reserva->usuario_id,
                                'estado' => $reserva->estado->nombre,
                            ];
                        }
                    }
                }
            }
        }

        return [
            'ocupaciones' => $ocupaciones,
            'seleccionItems' => $seleccionItems,
        ];
    }
}

