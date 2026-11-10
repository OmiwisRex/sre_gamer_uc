<section class="sre-admin-card" aria-labelledby="comp-reservar-title">
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-reservar-title">
            {{ isset($reservaActiva) && $reservaActiva ? 'Reserva' : 'Reservar' }}
        </h4>
        @if (isset($reservaActiva) && $reservaActiva)
            <span class="sre-badge">{{ $reservaActiva->estado?->nombre ?? 'Activa' }}</span>
        @endif
    </header>

    @if (isset($reservaActiva) && $reservaActiva)
        <div class="sre-admin-card-body">
            <dl class="sre-panel-content">
                @if ($reservaActiva->evento)
                    <div class="sre-detail-item">
                        <dt class="sre-detail-label">Evento</dt>
                        <dd class="sre-detail-value">{{ $reservaActiva->evento->nombre }}</dd>
                    </div>
                @endif
                <div class="sre-detail-item">
                    <dt class="sre-detail-label">Tipo</dt>
                    <dd class="sre-detail-value">{{ $reservaActiva->tipoDispo?->nombre ?? 'Sin tipo' }}</dd>
                </div>
                <div class="sre-detail-item">
                    <dt class="sre-detail-label">Inicio</dt>
                    <dd class="sre-detail-value">{{ \Illuminate\Support\Carbon::parse($reservaActiva->inicio)->format('d/m/Y - h:i A') }}</dd>
                </div>
                <div class="sre-detail-item">
                    <dt class="sre-detail-label">Duración</dt>
                    <dd class="sre-detail-value">{{ $reservaActiva->duracion_min }} min</dd>
                </div>
            </dl>
            <div class="sre-admin-actions">
                <form method="POST" action="{{ route('reservas.cancel', $reservaActiva) }}">
                    @csrf
                    <button class="sre-button sre-button--muted" type="submit">Cancelar</button>
                </form>
                @if ($reservaActiva->estado?->nombre === 'Activo')
                    @php
                        $inicioConversion = \Illuminate\Support\Carbon::parse($reservaActiva->inicio, config('app.timezone'));
                        $ahora = now(config('app.timezone'));
                        $puedeConvertir = $ahora->gte($inicioConversion->copy()->subMinutes(15))
                            && $ahora->lte($inicioConversion->copy()->addMinutes(15));
                    @endphp
                    <form method="POST" action="{{ route('reservas.associate', $reservaActiva) }}">
                        @csrf
                        <button class="sre-button" type="submit" @disabled(! $puedeConvertir)>Asociar</button>
                    </form>
                    @unless ($puedeConvertir)
                        <p class="sre-hint">La conversión estará disponible desde 15 minutos antes hasta 15 minutos después del inicio.</p>
                    @endunless
                @else
                    <form method="POST" action="{{ route('reservas.disassociate', $reservaActiva) }}">
                        @csrf
                        <button class="sre-button" type="submit">Desasociar</button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <div class="sre-admin-card-body">
            <form method="POST" action="{{ route('reservas.store') }}">
                @csrf
                <input type="hidden" name="usuario_id" value="{{ old('usuario_id', $jugador?->id) }}">
                <div class="sre-admin-form-grid-long">
                    <label class="sre-field">
                        <span class="sre-label">Tipo de dispositivo</span>
                        <select class="sre-select" name="tipo_dispo_id" required>
                            <option value="">Selecciona un tipo</option>
                            @foreach (($tiposDispo ?? collect()) as $tipoDispo)
                                <option value="{{ $tipoDispo->id }}"{{ old('tipo_dispo_id') == $tipoDispo->id ? ' selected' : '' }} title="{{ $tipoDispo->nombre }}">{{ \Illuminate\Support\Str::limit($tipoDispo->nombre, 18) }}</option>
                            @endforeach
                        </select>
                        @error('tipo_dispo_id')<span class="sre-hint" role="alert">{{ $message }}</span>@enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Evento <span class="sre-hint">(opcional)</span></span>
                        <select class="sre-select" name="evento_id">
                            <option value="">Sin evento</option>
                            @foreach (($eventos ?? collect()) as $evento)
                                <option value="{{ $evento->id }}"{{ old('evento_id') == $evento->id ? ' selected' : '' }} title="{{ $evento->nombre }}">{{ \Illuminate\Support\Str::limit($evento->nombre, 18) }}</option>
                            @endforeach
                        </select>
                        @error('evento_id')<span class="sre-hint" role="alert">{{ $message }}</span>@enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Fecha</span>
                        <input class="sre-input" type="date" name="fecha_reserva" value="{{ old('fecha_reserva', now()->format('Y-m-d')) }}" required>
                        @error('fecha_reserva')<span class="sre-hint" role="alert">{{ $message }}</span>@enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Hora</span>
                        <input class="sre-input" type="time" name="hora_reserva" value="{{ old('hora_reserva', now()->format('H:i')) }}" required>
                        @error('hora_reserva')<span class="sre-hint" role="alert">{{ $message }}</span>@enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Duración (minutos)</span>
                        <select class="sre-select" name="duracion_min">
                            @foreach ([30, 60, 90, 120, 150, 180] as $duracion)
                                <option value="{{ $duracion }}"{{ old('duracion_min', 30) == $duracion ? ' selected' : '' }}>{{ $duracion }} min</option>
                            @endforeach
                        </select>
                        @error('duracion_min')<span class="sre-hint" role="alert">{{ $message }}</span>@enderror
                    </label>
                </div>
                <div class="sre-admin-actions">
                    <button class="sre-button" type="submit" @disabled(! $jugador || $jugador->estado?->nombre !== 'Activo')>Crear reserva</button>
                </div>
            </form>
        </div>
    @endif
</section>
