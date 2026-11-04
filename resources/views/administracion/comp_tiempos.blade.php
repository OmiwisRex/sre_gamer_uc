@php
    $horasTimeline = $horasTimeline ?? range(6, 12);
    $ocupaciones = $ocupaciones ?? [];
    $dispositivos = $dispositivos ?? collect();
@endphp

<section class="sre-timeline-card" aria-labelledby="comp-tiempos-title">
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-tiempos-title">Tiempos</h4>
        <div class="sre-heading-actions">
            <span class="sre-label">Fecha del horario</span>
            <input class="sre-input" type="date" name="fecha_horario" value="{{ old('fecha_horario', $fechaSeleccionada ?? now()->toDateString()) }}">
        </div>
    </header>

    <div class="sre-timeline-scroll">
        <div class="sre-timeline-grid" style="--sre-timeline-columns: {{ max(count($horasTimeline), 1) }};" role="table" aria-label="Horario de dispositivos">
            <span class="sre-timeline-cell sre-timeline-cell--heading" role="columnheader">Dispositivo</span>
            @foreach ($horasTimeline as $hora)
                <span class="sre-timeline-cell sre-timeline-cell--heading {{ (int) $hora === (int) now()->format('G') ? 'sre-timeline-cell--now' : '' }}" role="columnheader">
                    {{ is_numeric($hora) ? sprintf('%02d:00', $hora) : $hora }}
                </span>
            @endforeach

            @forelse (($dispositivos ?? collect()) as $dispositivo)
                <span class="sre-timeline-cell sre-timeline-cell--device" role="rowheader" title="{{ $dispositivo->nombre }}">{{ $dispositivo->nombre }}</span>
                @foreach ($horasTimeline as $hora)
                    @php
                        $horaKey = is_numeric($hora) ? (int) $hora : (string) $hora;
                        $ocupacion = $ocupaciones[$dispositivo->id][$horaKey] ?? null;
                        $tipoOcupacion = is_array($ocupacion) ? ($ocupacion['tipo'] ?? 'occupied') : 'occupied';
                        $etiquetaOcupacion = is_array($ocupacion) ? ($ocupacion['etiqueta'] ?? 'Ocupado') : 'Ocupado';
                    @endphp
                    @if ($ocupacion)
                        <button
                            class="sre-timeline-cell sre-timeline-cell--{{ in_array($tipoOcupacion, ['association', 'event'], true) ? $tipoOcupacion : 'occupied' }}"
                            type="button"
                            role="cell"
                            aria-label="{{ $dispositivo->nombre }}, {{ is_numeric($hora) ? sprintf('%02d:00', $hora) : $hora }}: {{ $etiquetaOcupacion }}"
                            title="{{ $etiquetaOcupacion }}"
                        >{{ $etiquetaOcupacion }}</button>
                    @else
                        <span class="sre-timeline-cell" role="cell" aria-label="{{ $dispositivo->nombre }}, {{ is_numeric($hora) ? sprintf('%02d:00', $hora) : $hora }}: disponible"></span>
                    @endif
                @endforeach
            @empty
                <div class="sre-empty-state" style="grid-column: 1 / -1;">
                    <strong>Sin dispositivos para mostrar</strong>
                    <span>La programación aparecerá cuando se carguen los equipos.</span>
                </div>
            @endforelse
        </div>
    </div>

    <footer class="sre-timeline-legend" aria-label="Leyenda de horarios">
        <span class="sre-legend-item"><span class="sre-legend-swatch sre-legend-swatch--association"></span>Asociación</span>
        <span class="sre-legend-item"><span class="sre-legend-swatch"></span>Reserva</span>
        <span class="sre-legend-item"><span class="sre-legend-swatch sre-legend-swatch--event"></span>Evento</span>
    </footer>
</section>
