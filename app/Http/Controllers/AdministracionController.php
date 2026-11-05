<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Evento;
use App\Models\TipoDispo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdministracionController extends Controller
{
    public function index(Request $request): View
    {
        $queryReservationId = $request->query('reserva_id');
        $reservationId = is_scalar($queryReservationId)
            ? filter_var($queryReservationId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;

        return view('administracion.index', [
            'tiposDispo' => TipoDispo::query()->orderBy('nombre')->get(['id', 'nombre']),
            'eventos' => Evento::query()->orderBy('nombre')->get(['id', 'nombre']),
            'dispositivos' => Dispositivo::query()->orderBy('nombre')->get(['id', 'nombre']),
            'reserva_id' => $reservationId ?: null,
        ]);
    }
}
