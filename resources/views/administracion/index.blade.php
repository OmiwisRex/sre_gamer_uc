@extends('layout')

@section('title', 'Administración | SGUC')

@section('content')
    <section class="sre-page sre-admin-page" aria-labelledby="administracion-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="administracion-title">Administración</h2>
            </div>
        </header>

        <div class="sre-admin-dashboard">
            @include('administracion.mod_jugador', [
                'jugador' => $jugador ?? null,
                'reservaActiva' => $reservaActiva ?? null,
                'asociacionActiva' => $asociacionActiva ?? null,
                'historialAsociaciones' => $historialAsociaciones ?? collect(),
                'totalHoras' => $totalHoras ?? 0,
                'tiposDispo' => $tiposDispo ?? collect(),
                'eventos' => $eventos ?? collect(),
                'dispositivos' => $dispositivos ?? collect(),
                'reserva_id' => $reserva_id ?? null,
            ])

            @include('administracion.mod_alertas', [
                'alertas' => $alertas ?? collect(),
            ])

            @include('administracion.mod_horarios', [
                'fechaSeleccionada' => $fechaSeleccionada ?? null,
                'dispositivos' => $dispositivos ?? collect(),
                'horasTimeline' => $horasTimeline ?? range(1, 24),
                'ocupaciones' => $ocupaciones ?? [],
                'seleccionItems' => $seleccionItems ?? collect(),
            ])
        </div>
    </section>
@endsection
