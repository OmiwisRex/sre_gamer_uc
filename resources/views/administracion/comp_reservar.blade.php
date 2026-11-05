<section class="sre-admin-card" aria-labelledby="comp-reservar-title">
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-reservar-title">
            {{ isset($reservaActiva) && $reservaActiva ? 'Reserva' : 'Reservar' }}
        </h4>
        @if (isset($reservaActiva) && $reservaActiva)
            <span class="sre-badge">{{ $reservaActiva->estado?->nombre ?? 'Registrada' }}</span>
        @endif
    </header>

    @if (isset($reservaActiva) && $reservaActiva)
        <div class="sre-admin-card-body">
            <dl class="sre-detail-grid">
                <div class="sre-detail-item sre-detail-item--wide">
                    <dt class="sre-detail-label">Inicio</dt>
                    <dd class="sre-detail-value">{{ \Illuminate\Support\Carbon::parse($reservaActiva->inicio)->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="sre-detail-item">
                    <dt class="sre-detail-label">Tipo</dt>
                    <dd class="sre-detail-value">{{ $reservaActiva->tipoDispo?->nombre ?? 'Sin tipo' }}</dd>
                </div>
                <div class="sre-detail-item">
                    <dt class="sre-detail-label">Duración</dt>
                    <dd class="sre-detail-value">{{ $reservaActiva->duracion_min }} min</dd>
                </div>
                @if ($reservaActiva->evento)
                    <div class="sre-detail-item sre-detail-item--wide">
                        <dt class="sre-detail-label">Evento</dt>
                        <dd class="sre-detail-value">{{ $reservaActiva->evento->nombre }}</dd>
                    </div>
                @endif
            </dl>
            <div class="sre-admin-actions">
                <button class="sre-button sre-button--muted" type="button">Cancelar</button>
                <button class="sre-button" type="button">Asociar</button>
            </div>
        </div>
    @else
        <div class="sre-admin-card-body">
            <div class="sre-admin-form-grid-long">
                <label class="sre-field">
                    <span class="sre-label">Tipo de dispositivo</span>
                    <select class="sre-select" name="tipo_dispo_id">
                        <option value="">Selecciona un tipo</option>
                        @foreach (($tiposDispo ?? collect()) as $tipoDispo)
                            <option value="{{ $tipoDispo->id }}" title="{{ $tipoDispo->nombre }}">{{ \Illuminate\Support\Str::limit($tipoDispo->nombre, 18) }}</option>
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
                    <span class="sre-label">Fecha</span>
                    <input class="sre-input" type="date" name="fecha_reserva" value="{{ old('fecha_reserva', now()->format('Y-m-d')) }}">
                </label>
                <label class="sre-field">
                    <span class="sre-label">Hora</span>
                    <input class="sre-input" type="time" name="hora_reserva" value="{{ old('hora_reserva', now()->format('H:i')) }}">
                </label>
                <label class="sre-field">
                    <span class="sre-label">Duración (minutos)</span>
                    <select class="sre-select" name="duracion_min">
                        @foreach ([30, 60, 90, 120, 150, 180] as $duracion)
                            <option value="{{ $duracion }}">{{ $duracion }} min</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="sre-admin-actions">
                <button class="sre-button" type="button">Crear reserva</button>
            </div>
        </div>
    @endif
</section>
