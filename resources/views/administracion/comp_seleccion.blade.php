<section class="sre-selection-card" aria-labelledby="comp-seleccion-title">
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-seleccion-title">Selección</h4>
    </header>

    <div class="sre-selection-list">
        @forelse (($seleccionItems ?? collect()) as $item)
            @php
                $tipoItem = $item->tipo ?? 'reserva';
                $usuarioItem = $item->usuario ?? null;
            @endphp
            <article class="sre-selection-item sre-selection-item--{{ in_array($tipoItem, ['association', 'event'], true) ? $tipoItem : 'reservation' }}">
                <h5 class="sre-selection-name">
                    {{ trim(($usuarioItem?->nombres ?? '') . ' ' . ($usuarioItem?->apellidos ?? '')) ?: ($item->nombre ?? 'Elemento de horario') }}
                </h5>
                <div class="sre-selection-meta">
                    <span>{{ isset($item->inicio) ? \Illuminate\Support\Carbon::parse($item->inicio)->format('d/m/Y H:i') : 'Sin fecha' }}</span>
                    <span>{{ $item->duracion_min ?? '—' }} min</span>
                    <span>{{ $tipoItem === 'association' ? 'Asociación' : ($tipoItem === 'event' ? 'Evento' : 'Reserva') }}</span>
                </div>
            </article>
        @empty
            <div class="sre-empty-state">
                <strong>Sin elementos seleccionados</strong>
                <span>Selecciona una franja del horario para ver sus registros.</span>
            </div>
        @endforelse
    </div>
</section>
