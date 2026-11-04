<section class="sre-admin-module sre-admin-module--alerts" aria-labelledby="mod-alertas-title">
    <header class="sre-admin-module-heading">
        <div>
            <h3 class="sre-admin-module-title" id="mod-alertas-title">Alertas</h3>
            <p class="sre-admin-module-description">Asociaciones prolongadas y reservas próximas.</p>
        </div>
        <span class="sre-badge sre-badge--warning">{{ count($alertas ?? []) }}</span>
    </header>

    <div class="sre-alert-card">
        <div class="sre-alert-list">
            @forelse (($alertas ?? collect()) as $alerta)
                @php
                    $tipoAlerta = $alerta->tipo ?? 'reserva';
                    $usuarioAlerta = $alerta->usuario ?? null;
                    $dispositivoAlerta = $alerta->dispositivo ?? null;
                @endphp
                <button
                    class="sre-alert-item sre-alert-item--{{ $tipoAlerta === 'asociacion' ? 'association' : 'reservation' }}"
                    type="button"
                    aria-label="Ver alerta de {{ $usuarioAlerta?->nombres ?? 'jugador' }}"
                >
                    <span class="sre-record-top">
                        <span class="sre-alert-countdown">{{ $alerta->minutos ?? '—' }} min</span>
                        <span class="sre-badge {{ $tipoAlerta === 'asociacion' ? 'sre-badge--warning' : '' }}">
                            {{ $tipoAlerta === 'asociacion' ? 'Asociación' : 'Reserva próxima' }}
                        </span>
                    </span>
                    <span class="sre-alert-meta">
                        <span>{{ $dispositivoAlerta?->nombre ?? 'Sin dispositivo asignado' }}</span>
                    </span>
                    <span class="sre-alert-name">
                        {{ trim(($usuarioAlerta?->nombres ?? '') . ' ' . ($usuarioAlerta?->apellidos ?? '')) ?: 'Jugador sin identificar' }}
                    </span>
                </button>
            @empty
                <div class="sre-empty-state">
                    <strong>Sin alertas pendientes</strong>
                    <span>Las alertas aparecerán aquí.</span>
                </div>
            @endforelse
        </div>
    </div>
</section>
