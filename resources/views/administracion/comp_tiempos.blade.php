@php
    $horasTimeline = $horasTimeline ?? range(0, 23);
    $ocupaciones = $ocupaciones ?? [];
    $dispositivos = $dispositivos ?? collect();
    $fechaSeleccionada = $fechaSeleccionada ?? now()->toDateString();
    $hoy = now(config('app.timezone'))->toDateString();
    $esHoy = $fechaSeleccionada === $hoy;
    $horaActual = (int) now(config('app.timezone'))->format('G');
@endphp

<section class="sre-timeline-card" aria-labelledby="comp-tiempos-title" id="comp-tiempos">
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-tiempos-title">Tiempos</h4>
        <div class="sre-heading-actions">
            <span class="sre-label">Fecha del horario</span>
            <input class="sre-input sre-fecha-horario-input" type="date" name="fecha_horario" value="{{ $fechaSeleccionada }}" data-endpoint="{{ route('administracion.horario.get') }}">
        </div>
    </header>

    <div class="sre-timeline-scroll">
        <div class="sre-timeline-grid" style="--sre-timeline-columns: {{ max(count($horasTimeline), 1) }};" role="table" aria-label="Horario de dispositivos">
            <span class="sre-timeline-cell sre-timeline-cell--heading" role="columnheader">Dispositivo</span>
            @foreach ($horasTimeline as $hora)
                <span class="sre-timeline-cell sre-timeline-cell--heading {{ $esHoy && (int) $hora === $horaActual ? 'sre-timeline-cell--now' : '' }}" role="columnheader">
                    {{ sprintf('%02d:00', (int) $hora) }}
                </span>
            @endforeach

            @forelse ($dispositivos as $dispositivo)
                <span class="sre-timeline-cell sre-timeline-cell--device" role="rowheader" title="{{ $dispositivo->nombre }}">
                    {{ $dispositivo->nombre }}
                </span>
                @foreach ($horasTimeline as $hora)
                    @php
                        $horaKey = (int) $hora;
                        $ocupacion = $ocupaciones[$dispositivo->id][$horaKey] ?? null;
                    @endphp
                    @if ($ocupacion && is_array($ocupacion))
                        <button
                            class="sre-timeline-cell sre-timeline-cell--{{ $ocupacion['tipo'] }}"
                            type="button"
                            role="button"
                            aria-label="{{ $dispositivo->nombre }}, {{ sprintf('%02d:00', $horaKey) }}: {{ $ocupacion['etiqueta'] }}"
                            data-dispositivo-id="{{ $dispositivo->id }}"
                            data-hora="{{ $horaKey }}"
                            data-fecha="{{ $fechaSeleccionada }}"
                            title="{{ $ocupacion['etiqueta'] }}"
                            @click="$dispatch('horario-click', { dispositivoId: {{ $dispositivo->id }}, hora: {{ $horaKey }}, fecha: '{{ $fechaSeleccionada }}' })"
                        >{{ substr($ocupacion['etiqueta'], 0, 15) }}</button>
                    @else
                        <span class="sre-timeline-cell" role="cell" aria-label="{{ $dispositivo->nombre }}, {{ sprintf('%02d:00', $horaKey) }}: disponible"></span>
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
        <span class="sre-legend-item"><span class="sre-legend-swatch sre-legend-swatch--association-event"></span>Asociación Evento</span>
        <span class="sre-legend-item"><span class="sre-legend-swatch sre-legend-swatch--reservation"></span>Reserva</span>
        <span class="sre-legend-item"><span class="sre-legend-swatch sre-legend-swatch--reservation-event"></span>Reserva Evento</span>
    </footer>
</section>
