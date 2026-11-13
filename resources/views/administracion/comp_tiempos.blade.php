@php
    $franjas30 = $franjas30 ?? range(0, 47);
    $dispositivosPorTipo = $dispositivosPorTipo ?? collect();
    $reservasPorTipo = $reservasPorTipo ?? [];
    $ocupaciones = $ocupaciones ?? [];
    $eventosPorDispositivoFranja = $eventosPorDispositivoFranja ?? [];
    $fechaSeleccionada = $fechaSeleccionada ?? now(config('app.timezone'))->toDateString();
    $ahora = now(config('app.timezone'));
    $franjaActual = $fechaSeleccionada === $ahora->toDateString() ? $ahora->hour * 2 + (int) ($ahora->minute >= 30) : null;
    $clases = [
        'association' => 'asociacion',
        'association-event' => 'asociacion-evento',
        'reservation' => 'reserva',
        'reservation-event' => 'reserva-evento',
        'event' => 'evento',
    ];
@endphp

<section class="sre-timeline-card" aria-labelledby="comp-tiempos-title" id="comp-tiempos"
    data-selection-endpoint="{{ route('administracion.horario.seleccion') }}"
    data-csrf-token="{{ csrf_token() }}">
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-tiempos-title">Tiempos</h4>
        <div class="sre-heading-actions">
            <label class="sre-label" for="fecha-horario">Fecha del horario</label>
            <input id="fecha-horario" class="sre-input sre-fecha-horario-input" type="date" name="fecha_horario" value="{{ $fechaSeleccionada }}" data-endpoint="{{ route('administracion.horario.get') }}">
        </div>
    </header>

    <div class="sre-timeline-scroll">
        <div class="sre-timeline-grid" style="--sre-timeline-columns: {{ count($franjas30) }};" role="table" aria-label="Horario de dispositivos">
            <span class="sre-timeline-cell sre-timeline-cell--heading" role="columnheader">Dispositivo</span>
            @foreach ($franjas30 as $franja)
                @php
                    $hora = intdiv($franja, 2);
                    $titulo = sprintf('%02d:%s', $hora, $franja % 2 ? '30' : '00');
                    $etiqueta = $franja % 2 === 0 ? sprintf('%02d %s', $hora % 12 ?: 12, $hora < 12 ? 'Am' : 'Pm') : '';
                @endphp
                <span class="sre-timeline-cell sre-timeline-cell--heading {{ $franja === $franjaActual ? 'sre-timeline-cell--now' : '' }}" role="columnheader" title="{{ $titulo }}">{{ $etiqueta }}</span>
            @endforeach

            @forelse ($dispositivosPorTipo as $tipoId => $dispositivos)
                @php $tipoNombre = $dispositivos->first()->tipoDispo->nombre; @endphp
                <span class="sre-timeline-cell sre-timeline-cell--type-header" role="rowheader" title="{{ $tipoNombre }}">{{ $tipoNombre }}</span>
                @foreach ($franjas30 as $franja)
                    @php $celda = $reservasPorTipo[$tipoId][$franja] ?? null; @endphp
                    @if ($celda)
                        <button class="sre-timeline-cell sre-timeline-cell--{{ $clases[$celda['tipo']] }} {{ $franja === $franjaActual ? 'sre-timeline-cell--now' : '' }}"
                            type="button" data-tipo-dispo-id="{{ $tipoId }}" data-franja="{{ $franja }}"
                            aria-label="{{ $tipoNombre }}, {{ $celda['cantidad'] }} reservas, franja {{ $franja }}">{{ $celda['cantidad'] }}</button>
                    @else
                        <span class="sre-timeline-cell sre-timeline-cell--type-count {{ $franja === $franjaActual ? 'sre-timeline-cell--now' : '' }}" role="cell"></span>
                    @endif
                @endforeach
                @foreach ($dispositivos as $dispositivo)
                    <span class="sre-timeline-cell sre-timeline-cell--device" role="rowheader" title="{{ $dispositivo->nombre }}">{{ $dispositivo->nombre }}</span>
                    @foreach ($franjas30 as $franja)
                        @php $celda = $ocupaciones[$dispositivo->id][$franja] ?? null; @endphp
                        @if ($celda)
                            <button class="sre-timeline-cell sre-timeline-cell--{{ $clases[$celda['tipo']] }} {{ $franja === $franjaActual ? 'sre-timeline-cell--now' : '' }}"
                                type="button" data-dispositivo-id="{{ $dispositivo->id }}" data-franja="{{ $franja }}"
                                aria-label="{{ $dispositivo->nombre }}, {{ $celda['tipo'] === 'event' ? 'evento' : $celda['cantidad'] }}, franja {{ $franja }}">{{ $celda['tipo'] === 'event' ? '' : $celda['cantidad'] }}</button>
                        @else
                            <span class="sre-timeline-cell {{ $franja === $franjaActual ? 'sre-timeline-cell--now' : '' }}" role="cell"></span>
                        @endif
                    @endforeach
                @endforeach
            @empty
                <div class="sre-empty-state" style="grid-column: 1 / -1;">
                    <strong>Sin dispositivos para mostrar</strong>
                    <span>La programación aparecerá cuando se carguen los equipos.</span>
                </div>
            @endforelse
        </div>
    </div>
    <div class="sre-timeline-error" role="status" hidden></div>
    <footer class="sre-timeline-legend" aria-label="Leyenda de horarios">
        <span class="sre-legend-item"><span class="sre-legend-swatch sre-legend-swatch--evento"></span>Evento</span>
        <span class="sre-legend-item"><span class="sre-legend-swatch sre-legend-swatch--asociacion-evento"></span>Asociación Evento</span>
        <span class="sre-legend-item"><span class="sre-legend-swatch sre-legend-swatch--asociacion"></span>Asociación</span>
        <span class="sre-legend-item"><span class="sre-legend-swatch sre-legend-swatch--reserva-evento"></span>Reserva Evento</span>
        <span class="sre-legend-item"><span class="sre-legend-swatch sre-legend-swatch--reserva"></span>Reserva</span>
    </footer>
</section>
