<section class="sre-selection-card" aria-labelledby="comp-seleccion-title" id="comp-seleccion">
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-seleccion-title">Selección</h4>
    </header>

    <div class="sre-selection-list" id="sre-selection-list-items">
        @forelse (($seleccionItems ?? collect()) as $item)
            @php
                $tipoItem = $item['tipo'] ?? 'reserva';
                $usuarioId = $item['usuario_id'] ?? null;
            @endphp
            <button
                type="button"
                class="sre-selection-item sre-selection-item--{{ in_array($tipoItem, ['association', 'association-event'], true) ? 'association' : 'reservation' }}"
                @if ($usuarioId)
                    @click="document.querySelector('input[name=jugador_id]').value = '{{ $usuarioId }}'; document.querySelector('form').submit();"
                @endif
                style="{{ $usuarioId ? 'cursor: pointer;' : '' }}"
            >
                <h5 class="sre-selection-name">{{ $item['nombre'] ?? 'Elemento de horario' }}</h5>
                <div class="sre-selection-meta">
                    <span>{{ isset($item['inicio']) ? formatDateTime($item['inicio']) : 'Sin fecha' }}</span>
                    <span>{{ $item['duracion_min'] ?? '—' }} min</span>
                    <span>{{ in_array($tipoItem, ['association', 'association-event']) ? 'Asociación' : 'Reserva' }}</span>
                </div>
            </button>
        @empty
            <div class="sre-empty-state">
                <strong>Sin elementos seleccionados</strong>
                <span>Selecciona una franja del horario para ver sus registros.</span>
            </div>
        @endforelse
    </div>
</section>
