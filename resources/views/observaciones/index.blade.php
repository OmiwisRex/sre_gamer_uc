@extends('layout')

@section('title', 'Observaciones | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="observaciones-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="observaciones-title">Observaciones</h2>
            </div>
        </header>

        @if (session('success'))
            <p class="sre-badge" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <ul class="sre-hint" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <section class="sre-form-section sre-observation-compose" aria-labelledby="observation-create-title">
            <div class="sre-form-section-heading">
                <h3 class="sre-form-section-title" id="observation-create-title">Nueva observación</h3>
                <p class="sre-hint">Puedes relacionar el mensaje con una asociación reciente o registrarlo sin asociación.</p>
            </div>
            <form class="sre-observation-form" method="post" action="{{ route('observaciones.store') }}" data-observation-form>
                @csrf
                <label class="sre-field sre-observation-message">
                    <span class="sre-label">Mensaje</span>
                    <textarea
                        class="sre-textarea"
                        name="mensaje"
                        maxlength="256"
                        required
                        aria-describedby="observation-message-limit"
                        placeholder="Escribe la observación"
                    >{{ old('mensaje') }}</textarea>
                    <span class="sre-hint" id="observation-message-limit">Máximo 256 caracteres.</span>
                </label>
                <label class="sre-field sre-observation-association">
                    <span class="sre-label">Asociación reciente <span class="sre-hint">(opcional)</span></span>
                    <select class="sre-select" name="asociacion_id">
                        <option value="">Sin asociación</option>
                        @foreach ($asociacionesRecientes as $asociacion)
                            @php
                                $nombreAsociado = trim(($asociacion->usuario?->nombres ?? '') . ' ' . ($asociacion->usuario?->apellidos ?? ''));
                                $detalleAsociacion = $asociacion->dispositivo?->nombre ?? $asociacion->evento?->nombre ?? 'Sin dispositivo';
                                $opcionAsociacion = \Illuminate\Support\Carbon::parse($asociacion->inicio)->format('h:ia')
                                    . '-' . $detalleAsociacion
                                    . '-' . ($nombreAsociado ?: 'Jugador');
                            @endphp
                            <option
                                value="{{ $asociacion->id }}"
                                title="{{ $opcionAsociacion }}"
                                @selected((string) old('asociacion_id') === (string) $asociacion->id)
                            >{{ $opcionAsociacion }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sre-form-actions sre-observation-submit">
                    <button class="sre-button" type="submit">Crear observación</button>
                </div>
            </form>
        </section>

        <section class="sre-panel sre-observations-filter" aria-labelledby="observaciones-list-title">
            <div class="sre-panel-heading">
                <h3 class="sre-panel-title" id="observaciones-list-title">Consultar observaciones</h3>
            </div>
            <form
                class="sre-filter-form"
                method="get"
                action="{{ route('observaciones.index') }}"
                data-resource-search-form
                data-resource-type="observacion"
                data-search-url="{{ route('observaciones.search') }}"
                data-detail-url="{{ route('observaciones.detail', '__RECORD_ID__') }}"
            >
                <div class="sre-filter-grid sre-observations-filter-grid">
                    <label class="sre-field">
                        <span class="sre-label">Cédula del administrador</span>
                        <input class="sre-input" type="search" name="admin_cedula" value="{{ request('admin_cedula') }}" placeholder="Identificación">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Cédula o código del jugador</span>
                        <input class="sre-input" type="search" name="usuario_cedula" value="{{ request('usuario_cedula') }}" placeholder="Identificación o código">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Evento</span>
                        <select class="sre-select" name="evento_id">
                            <option value="">Todos</option>
                            @foreach ($eventos as $evento)
                                <option value="{{ $evento->id }}" @selected((string) request('evento_id') === (string) $evento->id)>{{ $evento->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Dispositivo</span>
                        <select class="sre-select" name="dispositivo_id">
                            <option value="">Todos</option>
                            @foreach ($dispositivos as $dispositivo)
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
                    <div class="sre-record-list" data-resource-results role="list"></div>
                    <div class="sre-empty-state" data-resource-results-status role="status" aria-live="polite">
                        <strong>No hay observaciones para mostrar</strong>
                        <span>Aplica filtros para buscar observaciones.</span>
                    </div>
                </div>
            </section>

            <section class="sre-panel sre-resource-detail" aria-labelledby="observacion-detail-title">
                <div class="sre-panel-heading">
                    <div>
                        <h3 class="sre-detail-title" id="observacion-detail-title" data-resource-detail-title>Detalle de observación</h3>
                        <div data-resource-detail-action></div>
                    </div>
                </div>
                <div class="sre-panel-content" data-resource-detail aria-live="polite">
                    <div class="sre-empty-state">
                        <strong>Selecciona una observación</strong>
                        <span>El mensaje completo y sus datos asociados se mostrarán aquí.</span>
                    </div>
                </div>
            </section>
        </div>
    </section>
@endsection
