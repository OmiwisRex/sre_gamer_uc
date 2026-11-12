<section class="sre-admin-card" aria-labelledby="comp-asociar-title">
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-asociar-title">
            {{ isset($asociacionActiva) && $asociacionActiva ? 'Asociación' : 'Asociar' }}
        </h4>
        @if ($jugador && isset($asociacionActiva) && $asociacionActiva)
            <span class="sre-badge">{{ $asociacionActiva->estado?->nombre ?? 'Activa' }}</span>
        @elseif ($jugador && ! empty($reserva_id))
            <span class="sre-badge">Reservado</span>
        @endif
    </header>

    @if ($jugador && isset($asociacionActiva) && $asociacionActiva)
        @php
            $observacionesAsociacion = $asociacionActiva->observaciones
                ->pluck('mensaje')
                ->filter()
                ->implode(' - ');
            $jugadoresCompartiendo = ($asociacionesCompartidas ?? collect())
                ->where('dispositivo_id', $asociacionActiva->dispositivo_id);
        @endphp
        <div class="sre-admin-card-body">
            <dl class="sre-detail-grid">
                @if ($asociacionActiva->evento)
                    <div class="sre-detail-item sre-detail-item--wide">
                        <dt class="sre-detail-label">Evento</dt>
                        <dd class="sre-detail-value">{{ $asociacionActiva->evento->nombre }}</dd>
                    </div>
                @endif
                <div class="sre-detail-item sre-detail-item--wide">
                    <dt class="sre-detail-label">Dispositivo</dt>
                    <dd class="sre-detail-value">{{ $asociacionActiva->dispositivo?->nombre ?? 'Sin dispositivo asignado' }}</dd>
                </div>
                <div class="sre-detail-item sre-detail-item--wide">
                    <dt class="sre-detail-label">Inicio</dt>
                    <dd class="sre-detail-value">{{ \Illuminate\Support\Carbon::parse($asociacionActiva->inicio)->format('d/m/Y - h:i A') }}</dd>
                </div>
            </dl>
            @if ($jugadoresCompartiendo->isNotEmpty())
                <p class="sre-hint">
                    También asociados a este dispositivo:
                    {{ $jugadoresCompartiendo->map(fn ($asociacion) => trim(($asociacion->usuario?->nombres ?? '').' '.($asociacion->usuario?->apellidos ?? '')).' ('.number_format((float) $asociacion->horas, 1).' h)')->implode(', ') }}
                </p>
            @endif
            @if ($observacionesAsociacion !== '')
                <label class="sre-field">
                    <span class="sre-label">Observaciones</span>
                    <textarea class="sre-textarea sre-ta-mas" rows="3" readonly>{{ $observacionesAsociacion }}</textarea>
                </label>
            @endif
            <div class="sre-admin-actions">
                <form method="POST" action="{{ route('asociaciones.close', $asociacionActiva) }}">
                    @csrf
                    <button class="sre-button sre-button--muted" type="submit">Cerrar</button>
                </form>
                @if ($asociacionActiva->estado?->nombre === 'Bloqueado')
                    <form method="POST" action="{{ route('asociaciones.continue', $asociacionActiva) }}">
                        @csrf
                        <button class="sre-button" type="submit">Continuar</button>
                    </form>
                @endif
            </div>
        </div>
    @elseif ($jugador)
        @php
            $reservaEnConversion = isset($reservaActiva)
                && $reservaActiva
                && $reservaActiva->estado?->nombre === 'Bloqueado'
                && (int) $reservaActiva->id === (int) ($reserva_id ?? 0);
            $asociacionesPorDispositivo = ($asociacionesCompartidas ?? collect())->groupBy('dispositivo_id');
        @endphp
        <div class="sre-admin-card-body">
            @if ($reservaEnConversion)
                <p class="sre-hint">
                    Conversión de reserva:
                    {{ $reservaActiva->tipoDispo?->nombre ?? 'Tipo de dispositivo' }}
                    @if ($reservaActiva->evento)
                        — {{ $reservaActiva->evento->nombre }}
                    @else
                        — reserva general
                    @endif
                </p>
            @endif
            <form method="POST" action="{{ route('asociaciones.store') }}">
                @csrf
                <input type="hidden" name="usuario_id" value="{{ old('usuario_id', $jugador?->id) }}">
                <input type="hidden" name="reserva_id" value="{{ $reservaEnConversion ? $reservaActiva->id : '' }}">
                <div class="sre-admin-form-grid sre-admin-form-grid--single">
                    @if ($reservaEnConversion)
                        <input type="hidden" name="evento_id" value="{{ $reservaActiva->evento_id }}">
                    @else
                        <label class="sre-field">
                            <span class="sre-label">Evento <span class="sre-hint">(opcional)</span></span>
                            <select class="sre-select" name="evento_id">
                                <option value="">Sin evento</option>
                                @foreach (($eventos ?? collect()) as $evento)
                                    <option value="{{ $evento->id }}"{{ old('evento_id') == $evento->id ? ' selected' : '' }} title="{{ $evento->nombre }}">
                                        {{ \Illuminate\Support\Str::limit($evento->nombre, 18) }}{{ $evento->auto_admin ? ' (administrado por organizadores)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('evento_id')<span class="sre-hint" role="alert">{{ $message }}</span>@enderror
                        </label>
                    @endif
                    <label class="sre-field">
                        <span class="sre-label">Dispositivo</span>
                        <select class="sre-select" name="dispositivo_id">
                            <option value="">Sin dispositivo (solo evento auto administrado)</option>
                            @foreach (($dispositivos ?? collect()) as $dispositivo)
                                @php($jugadoresCompartiendo = $asociacionesPorDispositivo->get($dispositivo->id, collect()))
                                <option value="{{ $dispositivo->id }}"{{ old('dispositivo_id') == $dispositivo->id ? ' selected' : '' }} title="{{ $dispositivo->nombre }}">{{ \Illuminate\Support\Str::limit($dispositivo->nombre, 18) }}{{ $jugadoresCompartiendo->isNotEmpty() ? ' ('.$jugadoresCompartiendo->count().' asociados)' : '' }}</option>
                            @endforeach
                        </select>
                        @error('dispositivo_id')<span class="sre-hint" role="alert">{{ $message }}</span>@enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Observación <span class="sre-hint">(opcional)</span></span>
                        <textarea class="sre-textarea sre-ta-mas" name="observacion" maxlength="256" placeholder="Añade un comentario">{{ old('observacion') }}</textarea>
                        @error('observacion')<span class="sre-hint" role="alert">{{ $message }}</span>@enderror
                    </label>
                </div>
                @if ($errors->has('confirmar_compartido'))
                    <label class="sre-field" role="alert">
                        <input type="checkbox" name="confirmar_compartido" value="1" @checked(old('confirmar_compartido'))>
                        {{ $errors->first('confirmar_compartido') }}
                    </label>
                @endif
                @if ($errors->has('confirmar_reserva'))
                    <label class="sre-field" role="alert">
                        <input type="checkbox" name="confirmar_reserva" value="1" @checked(old('confirmar_reserva'))>
                        {{ $errors->first('confirmar_reserva') }}
                    </label>
                @endif
                <div class="sre-admin-actions">
                    <button class="sre-button" type="submit" @disabled(! $jugador || $jugador->estado?->nombre !== 'Activo')>
                        {{ $errors->hasAny(['confirmar_compartido', 'confirmar_reserva']) ? 'Confirmar asociación' : 'Crear asociación' }}
                    </button>
                </div>
            </form>
        </div>
    @endif
</section>
