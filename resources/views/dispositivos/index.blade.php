@extends('layout')

@section('title', 'Dispositivos | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="dispositivos-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="dispositivos-title">Dispositivos</h2>
            </div>
            <a class="sre-button" href="{{ route('dispositivos.create') }}">Crear dispositivo</a>
        </header>

        <div class="sre-resource-layout sre-resource-layout--list-wide">
            <section class="sre-panel sre-resource-sidebar" aria-labelledby="dispositivos-list-title">
                <div class="sre-panel-heading">
                    <h3 class="sre-panel-title" id="dispositivos-list-title">Buscar dispositivos</h3>
                </div>
                <form class="sre-filter-form" action="{{ url('/dispositivos') }}" method="get">
                    <div class="sre-filter-grid-long">
                        <label class="sre-field">
                            <span class="sre-label">Tipo de dispositivo</span>
                            <select class="sre-select" name="tipo_dispo_id">
                                <option value="">Todos</option>
                                @foreach (($tiposDispo ?? collect()) as $tipoDispo)
                                    <option value="{{ $tipoDispo->id }}" @selected((string) request('tipo_dispo_id') === (string) $tipoDispo->id)>{{ $tipoDispo->nombre }}</option>
                                @endforeach
                            </select>
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
                        @forelse (($dispositivos ?? collect()) as $dispositivo)
                            <article class="sre-record">
                                <a href="{{ url('/dispositivos') }}?seleccionado={{ $dispositivo->id }}" aria-label="Ver detalle de {{ $dispositivo->nombre }}">
                                    <div class="sre-record-top">
                                        <h4 class="sre-record-name">{{ $dispositivo->nombre }}</h4>
                                        <span class="sre-badge">{{ $dispositivo->estado?->nombre ?? 'Sin estado' }}</span>
                                    </div>
                                    <div class="sre-record-meta">
                                        <span>{{ $dispositivo->tipoDispo?->nombre ?? 'Sin tipo' }}</span>
                                    </div>
                                </a>
                                <div class="sre-record-actions">
                                    <a class="sre-button sre-button--secondary sre-button--small" href="{{ url('/dispositivos/' . $dispositivo->id . '/edit') }}">Editar</a>
                                </div>
                            </article>
                        @empty
                            <div class="sre-empty-state">
                                <strong>No hay dispositivos para mostrar</strong>
                                <span>Los registros aparecerán aquí cuando estén disponibles.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="sre-panel sre-resource-detail" aria-labelledby="dispositivo-detail-title">
                @if (isset($dispositivoSeleccionado) && $dispositivoSeleccionado)
                    <div class="sre-panel-heading">
                        <div>
                            <h3 class="sre-detail-title" id="dispositivo-detail-title">{{ $dispositivoSeleccionado->nombre }}</h3>
                            <p class="sre-detail-subtitle">Información del dispositivo seleccionado</p>
                        </div>
                        <a class="sre-button sre-button--secondary sre-button--small" href="{{ url('/dispositivos/' . $dispositivoSeleccionado->id . '/edit') }}">Editar</a>
                    </div>
                    <div class="sre-panel-content">
                        <dl class="sre-detail-grid">
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Tipo de dispositivo</dt>
                                <dd class="sre-detail-value">{{ $dispositivoSeleccionado->tipoDispo?->nombre ?? 'Sin tipo' }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Estado</dt>
                                <dd class="sre-detail-value">{{ $dispositivoSeleccionado->estado?->nombre ?? 'Sin estado' }}</dd>
                            </div>
                            <div class="sre-detail-item sre-detail-item--wide">
                                <dt class="sre-detail-label">Descripción</dt>
                                <dd class="sre-detail-value">{{ $dispositivoSeleccionado->descripcion ?: 'Sin descripción' }}</dd>
                            </div>
                            @if ($dispositivoSeleccionado->direccion)
                                <div class="sre-detail-item sre-detail-item--wide">
                                    <dt class="sre-detail-label">Dirección</dt>
                                    <dd class="sre-detail-value">{{ $dispositivoSeleccionado->direccion }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @else
                    <div class="sre-panel-heading">
                        <h3 class="sre-panel-title" id="dispositivo-detail-title">Detalle del dispositivo</h3>
                    </div>
                    <div class="sre-panel-content">
                        <div class="sre-empty-state">
                            <strong>Selecciona un dispositivo</strong>
                            <span>Su tipo, estado y descripción se mostrarán en este espacio.</span>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </section>
@endsection
