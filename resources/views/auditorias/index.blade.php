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
                    <p class="sre-hint">Los eventos se generan automáticamente al utilizar los servicios.</p>
                </div>
            </div>

            <form class="sre-filter-form" method="get" action="{{ route('auditorias.index') }}">
                <div class="sre-audit-filters">
                    <label class="sre-field">
                        <span class="sre-label">Cédula del administrador</span>
                        <input class="sre-input" type="search" name="cedula" value="{{ request('cedula') }}" placeholder="Identificación">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Nombre del administrador</span>
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

            <div class="sre-audit-table-wrap">
                <table class="sre-audit-table">
                    <thead>
                        <tr>
                            <th scope="col">Fecha y hora</th>
                            <th scope="col">Acción</th>
                            <th scope="col">Tabla</th>
                            <th scope="col">Índice</th>
                            <th scope="col">Administrador</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($auditorias ?? collect()) as $auditoria)
                            <tr>
                                <td data-label="Fecha y hora">{{ \Illuminate\Support\Carbon::parse($auditoria->created_at)->format('d/m/Y H:i') }}</td>
                                <td data-label="Acción">{{ $auditoria->accion?->nombre ?? 'Sin acción' }}</td>
                                <td data-label="Tabla"><span class="sre-audit-table-name">{{ $auditoria->tabla }}</span></td>
                                <td data-label="Índice">{{ $auditoria->indice }}</td>
                                <td data-label="Usuario responsable">
                                    <span class="sre-audit-user">{{ trim(($auditoria->usuario?->nombres ?? '') . ' ' . ($auditoria->usuario?->apellidos ?? '')) ?: 'No disponible' }}</span>
                                    <span class="sre-audit-user-id">C.C. {{ $auditoria->usuario?->cedula ?? '—' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr class="sre-audit-empty-row">
                                <td colspan="5">
                                    <div class="sre-empty-state">
                                        <strong>No hay auditorías para mostrar</strong>
                                        <span>Los registros aparecerán aquí cuando estén disponibles.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection