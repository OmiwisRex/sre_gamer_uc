@extends('layout')

@section('title', 'Eventos | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="eventos-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="eventos-title">Eventos</h2>
            </div>
            <a class="sre-button" href="{{ route('eventos.create') }}">Crear evento</a>
        </header>

        <div class="sre-resource-layout sre-resource-layout--list-wide">
            <section class="sre-panel sre-resource-sidebar" aria-labelledby="eventos-list-title">
                <div class="sre-panel-heading">
                    <h3 class="sre-panel-title" id="eventos-list-title">Buscar eventos</h3>
                </div>
                <form class="sre-filter-form" action="{{ url('/eventos') }}" method="get">
                    <div class="sre-filter-grid-long">
                        <label class="sre-field">
                            <span class="sre-label">Cédula del organizador</span>
                            <input class="sre-input" type="search" name="usuario_cedula" value="{{ request('usuario_cedula') }}" placeholder="Identificación">
                        </label>
                        <label class="sre-field">
                            <span class="sre-label">Estado</span>
                            <select class="sre-select" name="estado_id">
                                <option value="">Todos</option>
                                @foreach (($estados ?? collect()) as $estado)
                                    <option value="{{ $estado->id }}" @selected((string) request('estado_id') === (string) $estado->id)>{{ $estado->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="sre-button sre-button--small" type="submit">Aplicar filtros</button>
                    </div>
                </form>
                <div class="sre-panel-content">
                    <div class="sre-record-list">
                        @forelse (($eventos ?? collect()) as $evento)
                            <article class="sre-record">
                                <a href="{{ url('/eventos') }}?seleccionado={{ $evento->id }}" aria-label="Ver detalle de {{ $evento->nombre }}">
                                    <div class="sre-record-top">
                                        <h4 class="sre-record-name">{{ $evento->nombre }}</h4>
                                        <span class="sre-badge">{{ $evento->estado?->nombre ?? 'Sin estado' }}</span>
                                    </div>
                                    <div class="sre-record-meta">
                                        <span>{{ $evento->inicio ? \Illuminate\Support\Carbon::parse($evento->inicio)->format('d/m/Y H:i') : 'Fecha por definir' }}</span>
                                        <span>Aforo: {{ $evento->aforo }}</span>
                                    </div>
                                </a>
                                <div class="sre-record-actions">
                                    <a class="sre-button sre-button--secondary sre-button--small" href="{{ url('/eventos/' . $evento->id . '/edit') }}">Editar</a>
                                </div>
                            </article>
                        @empty
                            <div class="sre-empty-state">
                                <strong>No hay eventos para mostrar</strong>
                                <span>Los registros aparecerán aquí cuando estén disponibles.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="sre-panel sre-resource-detail" aria-labelledby="evento-detail-title">
                @if (isset($eventoSeleccionado) && $eventoSeleccionado)
                    <div class="sre-panel-heading">
                        <div>
                            <h3 class="sre-detail-title" id="evento-detail-title">{{ $eventoSeleccionado->nombre }}</h3>
                            <p class="sre-detail-subtitle">Información del evento seleccionado</p>
                        </div>
                        <a class="sre-button sre-button--secondary sre-button--small" href="{{ url('/eventos/' . $eventoSeleccionado->id . '/edit') }}">Editar</a>
                    </div>
                    <div class="sre-panel-content">
                        <dl class="sre-detail-grid">
                            <div class="sre-detail-item sre-detail-item--wide">
                                <dt class="sre-detail-label">Descripción</dt>
                                <dd class="sre-detail-value">{{ $eventoSeleccionado->descripcion }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Organizador</dt>
                                <dd class="sre-detail-value">{{ trim(($eventoSeleccionado->usuario?->nombres ?? '') . ' ' . ($eventoSeleccionado->usuario?->apellidos ?? '')) ?: 'Sin organizador' }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Inicio</dt>
                                <dd class="sre-detail-value">{{ $eventoSeleccionado->inicio ? \Illuminate\Support\Carbon::parse($eventoSeleccionado->inicio)->format('d/m/Y H:i') : 'Sin fecha' }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Duración</dt>
                                <dd class="sre-detail-value">{{ $eventoSeleccionado->duracion_horas }} horas</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Aforo aproximado</dt>
                                <dd class="sre-detail-value">{{ $eventoSeleccionado->aforo }} personas</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Estado</dt>
                                <dd class="sre-detail-value">{{ $eventoSeleccionado->estado?->nombre ?? 'Sin estado' }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Administración</dt>
                                <dd class="sre-detail-value">{{ $eventoSeleccionado->auto_admin ? 'Auto administrado' : 'Administrado por encargados' }}</dd>
                            </div>
                            <div class="sre-detail-item sre-detail-item--wide">
                                <dt class="sre-detail-label">Dispositivos vinculados</dt>
                                <dd class="sre-detail-value">
                                    @forelse (($eventoSeleccionado->dispositivos ?? collect()) as $dispositivo)
                                        <span class="sre-badge">{{ $dispositivo->nombre }}</span>
                                    @empty
                                        Sin dispositivos vinculados
                                    @endforelse
                                </dd>
                            </div>
                        </dl>
                    </div>
                @else
                    <div class="sre-panel-heading">
                        <h3 class="sre-panel-title" id="evento-detail-title">Detalle del evento</h3>
                    </div>
                    <div class="sre-panel-content">
                        <div class="sre-empty-state">
                            <strong>Selecciona un evento</strong>
                            <span>Su descripción, programación y dispositivos se mostrarán en este espacio.</span>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </section>
@endsection
