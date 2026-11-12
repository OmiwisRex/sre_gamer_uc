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
            <section class="sre-panel sre-resource-sidebar" aria-labelledby="dispositivos-list-title">
                <div class="sre-panel-heading">
                    <h3 class="sre-panel-title" id="dispositivos-list-title">Buscar dispositivos</h3>
                </div>
                <form
                    class="sre-filter-form"
                    action="{{ route('dispositivos.index') }}"
                    method="get"
                    data-resource-search-form
                    data-resource-type="dispositivo"
                    data-load-initial
                    data-search-url="{{ route('dispositivos.search') }}"
                    data-detail-url="{{ route('dispositivos.detail', '__RECORD_ID__') }}"
                >
                    <div class="sre-filter-grid-long">
                        <label class="sre-field">
                            <span class="sre-label">Tipo de dispositivo</span>
                            <select class="sre-select" name="tipo_dispo_id">
                                <option value="">Todos</option>
                                @foreach ($tiposDispo as $tipoDispo)
                                    <option value="{{ $tipoDispo->id }}" @selected((string) request('tipo_dispo_id') === (string) $tipoDispo->id)>{{ $tipoDispo->nombre }}</option>
                                @endforeach
                            </select>
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
                        <strong>No hay dispositivos para mostrar</strong>
                        <span>Aplica filtros para buscar dispositivos.</span>
                    </div>
                </div>
            </section>

            <section class="sre-panel sre-resource-detail" aria-labelledby="dispositivo-detail-title">
                <div class="sre-panel-heading">
                    <div>
                        <h3 class="sre-detail-title" id="dispositivo-detail-title" data-resource-detail-title>Detalle del dispositivo</h3>
                        <div data-resource-detail-action></div>
                    </div>
                </div>
                <div class="sre-panel-content" data-resource-detail aria-live="polite">
                    <div class="sre-empty-state">
                        <strong>Selecciona un dispositivo</strong>
                        <span>Su tipo, estado y descripción se mostrarán en este espacio.</span>
                    </div>
                </div>
            </section>
        </div>
    </section>
@endsection
