@extends('layout')

@section('title', 'Auditorías | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="auditorias-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="auditorias-title">Auditorías</h2>
            </div>
        </header>

        <section class="sre-panel sre-audit-panel" aria-labelledby="auditorias-list-title">
            <div class="sre-panel-heading">
                <div>
                    <h3 class="sre-panel-title" id="auditorias-list-title">Registro de auditoría</h3>
                    <p class="sre-hint">Consulta los registros de auditoría disponibles.</p>
                </div>
            </div>

            <form class="sre-filter-form" method="get" action="{{ route('auditorias.index') }}"
                data-resource-search-form data-resource-type="auditoria"
                data-search-url="{{ route('auditorias.search') }}" data-load-initial>
                <div class="sre-audit-filters">
                    <label class="sre-field">
                        <span class="sre-label">Cédula del usuario</span>
                        <input class="sre-input" type="search" name="cedula" value="{{ request('cedula') }}" placeholder="Identificación">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Nombre del usuario</span>
                        <input class="sre-input" type="search" name="nombre" value="{{ request('nombre') }}" placeholder="Nombre o apellido">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Acción</span>
                        <select class="sre-select" name="accion_id">
                            <option value="">Todas</option>
                            @foreach (($acciones ?? collect()) as $accion)
                                <option value="{{ $accion->id }}" @selected((string) request('accion_id') === (string) $accion->id)>{{ $accion->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Tabla</span>
                        <select class="sre-select" name="tabla">
                            <option value="">Todas</option>
                            @foreach (($tablas ?? []) as $tabla)
                                <option value="{{ $tabla }}" @selected(request('tabla') === $tabla)>
                                    {{ $tabla }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Día del registro</span>
                        <input class="sre-input" type="date" name="fecha" value="{{ request('fecha') }}">
                    </label>
                </div>
                <div class="sre-filter-actions">
                    <button class="sre-button sre-button--small" type="submit">Aplicar filtros</button>
                </div>
            </form>

            <div class="sre-audit-table-wrap" data-resource-scroll>
                <table class="sre-audit-table">
                    <thead>
                        <tr>
                            <th scope="col">Fecha y hora</th>
                            <th scope="col">Acción</th>
                            <th scope="col">Tabla</th>
                            <th scope="col">Índice</th>
                            <th scope="col">Usuario</th>
                        </tr>
                    </thead>
                    <tbody data-resource-results></tbody>
                </table>
                <div class="sre-empty-state" data-resource-results-status role="status" aria-live="polite">
                    <strong>No hay auditorías para mostrar</strong>
                </div>
            </div>
        </section>
    </section>
@endsection