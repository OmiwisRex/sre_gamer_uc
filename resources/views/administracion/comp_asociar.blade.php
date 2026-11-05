<section class="sre-admin-card" aria-labelledby="comp-asociar-title">
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-asociar-title">
            {{ isset($asociacionActiva) && $asociacionActiva ? 'Asociación' : 'Asociar' }}
        </h4>
        @if (isset($asociacionActiva) && $asociacionActiva)
            <span class="sre-badge">{{ $asociacionActiva->estado?->nombre ?? 'Activa' }}</span>
        @elseif (! empty($reserva_id))
            <span class="sre-badge">Reserva</span>
        @endif
    </header>

    @if (isset($asociacionActiva) && $asociacionActiva)
        <div class="sre-admin-card-body">
            <dl class="sre-detail-grid">
                <div class="sre-detail-item sre-detail-item--wide">
                    <dt class="sre-detail-label">Inicio</dt>
                    <dd class="sre-detail-value">{{ \Illuminate\Support\Carbon::parse($asociacionActiva->inicio)->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="sre-detail-item sre-detail-item--wide">
                    <dt class="sre-detail-label">Dispositivo</dt>
                    <dd class="sre-detail-value">{{ $asociacionActiva->dispositivo?->nombre ?? 'Sin dispositivo asignado' }}</dd>
                </div>
                @if ($asociacionActiva->evento)
                    <div class="sre-detail-item sre-detail-item--wide">
                        <dt class="sre-detail-label">Evento</dt>
                        <dd class="sre-detail-value">{{ $asociacionActiva->evento->nombre }}</dd>
                    </div>
                @endif
            </dl>
            <div class="sre-admin-actions">
                <button class="sre-button sre-button--muted" type="button">Cerrar asociación</button>
            </div>
        </div>
    @else
        <div class="sre-admin-card-body">
            <div class="sre-admin-form-grid sre-admin-form-grid--single">
                <label class="sre-field">
                    <span class="sre-label">Dispositivo</span>
                    <select class="sre-select" name="dispositivo_id">
                        <option value="">No (Evento)</option>
                        @foreach (($dispositivos ?? collect()) as $dispositivo)
                            <option value="{{ $dispositivo->id }}" title="{{ $dispositivo->nombre }}">{{ \Illuminate\Support\Str::limit($dispositivo->nombre, 18) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="sre-field">
                    <span class="sre-label">Evento <span class="sre-hint">(opcional)</span></span>
                    <select class="sre-select" name="evento_id">
                        <option value="">Sin evento</option>
                        @foreach (($eventos ?? collect()) as $evento)
                            <option value="{{ $evento->id }}" title="{{ $evento->nombre }}">{{ \Illuminate\Support\Str::limit($evento->nombre, 18) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="sre-field">
                    <span class="sre-label">Observación <span class="sre-hint">(opcional)</span></span>
                    <textarea class="sre-textarea sre-ta-mas" name="observacion" placeholder="Añade un comentario"></textarea>
                </label>
            </div>
            <input type="hidden" name="reserva_id" value="{{ old('reserva_id', $reserva_id ?? '') }}">
            <div class="sre-admin-actions">
                <button class="sre-button" type="button">Crear asociación</button>
            </div>
        </div>
    @endif
</section>
