<section class="sre-admin-module sre-admin-module--player" aria-labelledby="mod-jugador-title">
    <header class="sre-admin-module-heading">
        <div class="sre-admin-card-heading">
            <h3 class="sre-admin-module-title" id="mod-jugador-title">Jugador</h3>
            @if (session('success'))
                <p class="sre-badge" role="status">{{ session('success') }}</p>
            @endif
        </div>
    </header>

    <div class="sre-player-layout">
        <div class="sre-player-search">
            @include('administracion.comp_busqueda', [
                'jugador' => $jugador ?? null,
            ])
        </div>

        <div class="sre-player-operations">
            @include('administracion.comp_reservar', [
                'reservaActiva' => $reservaActiva ?? null,
                'tiposDispo' => $tiposDispo ?? collect(),
                'eventos' => $eventos ?? collect(),
            ])

            @include('administracion.comp_asociar', [
                'asociacionActiva' => $asociacionActiva ?? null,
                'reservaActiva' => $reservaActiva ?? null,
                'dispositivos' => $dispositivos ?? collect(),
                'eventos' => $eventos ?? collect(),
                'reserva_id' => $reserva_id ?? null,
            ])

            @include('administracion.comp_horas', [
                'historialAsociaciones' => $historialAsociaciones ?? collect(),
                'totalHoras' => $totalHoras ?? 0,
            ])
        </div>
    </div>
</section>
