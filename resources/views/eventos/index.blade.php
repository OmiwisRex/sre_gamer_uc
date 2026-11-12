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

        <div class="sre-resource-layout sre-resource-layout--list-wide">
            <section class="sre-panel sre-resource-sidebar" aria-labelledby="eventos-list-title">
                <div class="sre-panel-heading">
                    <h3 class="sre-panel-title" id="eventos-list-title">Buscar eventos</h3>
                </div>
                <form
                    class="sre-filter-form"
                    action="{{ route('eventos.index') }}"
                    method="get"
                    data-resource-search-form
                    data-resource-type="evento"
                    data-load-initial
                    data-search-url="{{ route('eventos.search') }}"
                    data-detail-url="{{ route('eventos.detail', '__RECORD_ID__') }}"
                >
                    <div class="sre-filter-grid-long">
                        <label class="sre-field">
                            <span class="sre-label">Cédula del organizador</span>
                            <input class="sre-input" type="search" name="usuario_cedula" value="{{ request('usuario_cedula') }}" placeholder="Identificación">
                        </label>
                        <label class="sre-field">
                            <span class="sre-label">Estado</span>
                            <select class="sre-select" name="estado_id">
                                <option value="">Todos</option>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado->id }}" @selected((string) request('estado_id') === (string) $estado->id)>{{ $estado->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="sre-button sre-button--small" type="submit">Aplicar filtros</button>
                    </div>
                </form>
                <div class="sre-panel-content">
                    <div class="sre-record-list" data-resource-results role="list"></div>
                    <div class="sre-empty-state" data-resource-results-status role="status" aria-live="polite">
                        <strong>No hay eventos para mostrar</strong>
                        <span>Aplica filtros para buscar eventos.</span>
                    </div>
                </div>
            </section>

            <section class="sre-panel sre-resource-detail" aria-labelledby="evento-detail-title">
                <div class="sre-panel-heading">
                    <div>
                        <h3 class="sre-detail-title" id="evento-detail-title" data-resource-detail-title>Detalle del evento</h3>
                        <div data-resource-detail-action></div>
                    </div>
                </div>
                <div class="sre-panel-content" data-resource-detail aria-live="polite">
                    <div class="sre-empty-state">
                        <strong>Selecciona un evento</strong>
                        <span>Su descripción, programación y dispositivos se mostrarán en este espacio.</span>
                    </div>
                </div>
            </section>
        </div>
    </section>
@endsection
