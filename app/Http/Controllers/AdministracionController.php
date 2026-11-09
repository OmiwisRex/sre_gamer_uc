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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdministracionController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'jugador_id' => ['nullable', 'string', 'max:24'],
        ]);
        $queryReservationId = $request->query('reserva_id');
        $reservationId = is_scalar($queryReservationId)
            ? filter_var($queryReservationId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
        $identificacionJugador = trim($validated['jugador_id'] ?? '');
        $jugador = null;
        $reservaActiva = null;
        $asociacionActiva = null;
        $historialAsociaciones = collect();

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
                        $query->where('nombre', 'Activo');
                    })
                    ->orderByDesc('inicio')
                    ->orderByDesc('id')
                    ->first();

                $asociacionActiva = Asociacion::query()
                    ->with(['estado:id,nombre', 'dispositivo:id,nombre', 'evento:id,nombre'])
                    ->where('usuario_id', $jugador->id)
                    ->whereHas('estado', function (Builder $query): void {
                        $query->whereIn('nombre', ['Activo', 'Bloqueado']);
                    })
                    ->orderByDesc('inicio')
                    ->orderByDesc('id')
                    ->first();

                $historialAsociaciones = Asociacion::query()
                    ->with('dispositivo:id,nombre')
                    ->where('usuario_id', $jugador->id)
                    ->whereDate('inicio', today())
                    ->orderByDesc('inicio')
                    ->orderByDesc('id')
                    ->get();

                $historialAsociaciones->each(function (Asociacion $asociacion): void {
                    $inicio = Carbon::parse($asociacion->inicio);
                    $final = Carbon::parse($asociacion->final);
                    $asociacion->setAttribute(
                        'horas',
                        ($final->getTimestamp() - $inicio->getTimestamp()) / 3600,
                    );
                });
            }
        }

        return view('administracion.index', [
            'tiposDispo' => TipoDispo::query()->orderBy('nombre')->get(['id', 'nombre']),
            'eventos' => Evento::query()->orderBy('nombre')->get(['id', 'nombre']),
            'dispositivos' => Dispositivo::query()->orderBy('nombre')->get(['id', 'nombre']),
            'jugador' => $jugador,
            'jugadorId' => $identificacionJugador,
            'reservaActiva' => $reservaActiva,
            'asociacionActiva' => $asociacionActiva,
            'historialAsociaciones' => $historialAsociaciones,
            'totalHoras' => $historialAsociaciones->sum('horas'),
            'reserva_id' => $reservaActiva?->id ?: $reservationId ?: null,
        ]);
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

        $createReserva->handle($jugador->id, [
            'tipo_dispo_id' => $validated['tipo_dispo_id'],
            'evento_id' => $validated['evento_id'] ?? null,
            'inicio' => $inicio->toDateTimeString(),
            'duracion_min' => $validated['duracion_min'],
        ]);

        return redirect()
            ->route('administracion.index', ['jugador_id' => $jugador->cedula])
            ->with('success', 'Reserva creada correctamente.');
    }

    public function storeAssociation(Request $request, CreateAsociacionService $createAsociacion): RedirectResponse
    {
        $validated = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'dispositivo_id' => ['nullable', 'integer', 'exists:dispositivos,id', 'required_without:evento_id'],
            'evento_id' => ['nullable', 'integer', 'exists:eventos,id', 'required_without:dispositivo_id'],
            'reserva_id' => ['nullable', 'integer', 'exists:reservas,id'],
            'observacion' => ['nullable', 'string', 'max:256'],
        ]);
        $jugador = Usuario::query()->findOrFail($validated['usuario_id']);

        $createAsociacion->handle(
            $jugador->id,
            (int) $request->user()->getAuthIdentifier(),
            [
                'dispositivo_id' => $validated['dispositivo_id'] ?? null,
                'evento_id' => $validated['evento_id'] ?? null,
                'reserva_id' => $validated['reserva_id'] ?? null,
            ],
            $validated['observacion'] ?? null,
        );

        return redirect()
            ->route('administracion.index', ['jugador_id' => $jugador->cedula])
            ->with('success', 'Asociación creada correctamente.');
    }
}
