@extends('layout')

@section('title', 'Observaciones | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="observaciones-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="observaciones-title">Observaciones</h2>
            </div>
        </header>

        <section class="sre-form-section sre-observation-compose" aria-labelledby="observation-create-title">
            <div class="sre-form-section-heading">
                <h3 class="sre-form-section-title" id="observation-create-title">Nueva observación</h3>
                <p class="sre-hint">Puedes relacionar el mensaje con una asociación reciente o registrarlo sin asociación.</p>
            </div>
            <form class="sre-observation-form">
                <label class="sre-field sre-observation-message">
                    <span class="sre-label">Mensaje</span>
                    <textarea class="sre-textarea" name="mensaje" maxlength="256"
                        placeholder="Escribe la observación"></textarea>
                </label>
                <label class="sre-field sre-observation-association">
                    <span class="sre-label">Asociación reciente <span class="sre-hint">(opcional)</span></span>
                    <select class="sre-select" name="asociacion_id">
                        <option value="">Sin asociación</option>
                        @foreach (($asociacionesRecientes ?? collect()) as $asociacion)
                            @php
                                $nombreAsociado = trim(($asociacion->usuario?->nombres ?? '') . ' ' . ($asociacion->usuario?->apellidos ?? ''));
                                $detalleAsociacion = $asociacion->dispositivo?->nombre ?? $asociacion->evento?->nombre ?? 'Sin dispositivo';
                            @endphp
                            <option value="{{ $asociacion->id }}">
                                #{{ $asociacion->id }} · {{ $nombreAsociado ?: 'Jugador' }} · {{ $detalleAsociacion }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <div class="sre-form-actions sre-observation-submit">
                    <button class="sre-button" type="button">Crear observación</button>
                </div>
            </form>
        </section>

        <section class="sre-panel sre-observations-filter" aria-labelledby="observaciones-list-title">
            <div class="sre-panel-heading">
                <h3 class="sre-panel-title" id="observaciones-list-title">Consultar observaciones</h3>
            </div>
            <form class="sre-filter-form" method="get" action="{{ route('observaciones.index') }}">
                <div class="sre-filter-grid sre-observations-filter-grid">
                    <label class="sre-field">
                        <span class="sre-label">Cédula del administrador</span>
                        <input class="sre-input" type="search" name="admin_cedula" value="{{ request('admin_cedula') }}" placeholder="Identificación">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Cédula del jugador</span>
                        <input class="sre-input" type="search" name="usuario_cedula" value="{{ request('usuario_cedula') }}" placeholder="Identificación">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Evento</span>
                        <select class="sre-select" name="evento_id">
                            <option value="">Todos</option>
                            @foreach (($eventos ?? collect()) as $evento)
                                <option value="{{ $evento->id }}" @selected((string) request('evento_id') === (string) $evento->id)>{{ $evento->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Dispositivo</span>
                        <select class="sre-select" name="dispositivo_id">
                            <option value="">Todos</option>
                            @foreach (($dispositivos ?? collect()) as $dispositivo)
                                <option value="{{ $dispositivo->id }}" @selected((string) request('dispositivo_id') === (string) $dispositivo->id)>{{ $dispositivo->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Fecha de registro</span>
                        <input class="sre-input" type="date" name="fecha" value="{{ request('fecha') }}">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Relación</span>
                        <select class="sre-select" name="con_asociacion">
                            <option value="">Todas</option>
                            <option value="1" @selected(request('con_asociacion') === '1')>Con asociación</option>
                            <option value="0" @selected(request('con_asociacion') === '0')>Sin asociación</option>
                        </select>
                    </label>
                </div>
                <div class="sre-filter-actions">
                    <button class="sre-button sre-button--small" type="submit">Aplicar filtros</button>
                </div>
            </form>
        </section>

        <div class="sre-resource-layout sre-observations-layout sre-observations-results">
            <section class="sre-panel sre-resource-sidebar" aria-labelledby="observaciones-results-title">
                <div class="sre-panel-heading">
                    <h3 class="sre-panel-title" id="observaciones-results-title">Resultados</h3>
                </div>
                <div class="sre-panel-content">
                    <div class="sre-record-list">
                        @forelse (($observaciones ?? collect()) as $observacion)
                            <a
                                class="sre-record sre-observation-record"
                                href="{{ route('observaciones.index', ['seleccionado' => $observacion->id]) }}"
                                @if (isset($observacionSeleccionada) && $observacionSeleccionada && (string) $observacionSeleccionada->id === (string) $observacion->id) aria-current="true" @endif
                            >
                                <div class="sre-record-top">
                                    <h4 class="sre-record-name">#{{ $observacion->id }} · {{ \Illuminate\Support\Carbon::parse($observacion->created_at)->format('d/m/Y H:i') }}</h4>
                                    <span class="sre-badge {{ $observacion->asociacion ? '' : 'sre-badge--neutral' }}">
                                        {{ $observacion->asociacion ? 'Asociada' : 'General' }}
                                    </span>
                                </div>
                                <p class="sre-observation-preview">{{ $observacion->mensaje }}</p>
                            </a>
                        @empty
                            <div class="sre-empty-state">
                                <strong>No hay observaciones para mostrar</strong>
                                <span>Los registros aparecerán aquí cuando estén disponibles.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="sre-panel sre-resource-detail" aria-labelledby="observacion-detail-title">
                @if (isset($observacionSeleccionada) && $observacionSeleccionada)
                    @php
                        $asociacionSeleccionada = $observacionSeleccionada->asociacion;
                        $adminObservacion = $observacionSeleccionada->usuario;
                        $jugadorObservacion = $asociacionSeleccionada?->usuario;
                    @endphp
                    <div class="sre-panel-heading">
                        <div>
                            <h3 class="sre-detail-title" id="observacion-detail-title">Detalle de observación</h3>
                            <p class="sre-detail-subtitle">{{ \Illuminate\Support\Carbon::parse($observacionSeleccionada->created_at)->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="sre-panel-content">
                        <dl class="sre-detail-grid">
                            <div class="sre-detail-item sre-detail-item--wide">
                                <dt class="sre-detail-label">Mensaje</dt>
                                <dd class="sre-detail-value sre-observation-full-message">{{ $observacionSeleccionada->mensaje }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Administrador</dt>
                                <dd class="sre-detail-value">
                                    {{ trim(($adminObservacion?->nombres ?? '') . ' ' . ($adminObservacion?->apellidos ?? '')) ?: 'No disponible' }}
                                </dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Jugador asociado</dt>
                                <dd class="sre-detail-value">
                                    {{ trim(($jugadorObservacion?->nombres ?? '') . ' ' . ($jugadorObservacion?->apellidos ?? '')) ?: 'Sin asociación' }}
                                </dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Dispositivo</dt>
                                <dd class="sre-detail-value">{{ $asociacionSeleccionada?->dispositivo?->nombre ?? 'No aplica' }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Evento</dt>
                                <dd class="sre-detail-value">{{ $asociacionSeleccionada?->evento?->nombre ?? 'No aplica' }}</dd>
                            </div>
                        </dl>
                    </div>
                @else
                    <div class="sre-panel-heading">
                        <h3 class="sre-panel-title" id="observacion-detail-title">Detalle de observación</h3>
                    </div>
                    <div class="sre-panel-content">
                        <div class="sre-empty-state">
                            <strong>Selecciona una observación</strong>
                            <span>El mensaje completo y sus datos asociados se mostrarán aquí.</span>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </section>
@endsection