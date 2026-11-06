@extends('layout')

@section('title', 'Usuarios | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="usuarios-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="usuarios-title">Usuarios</h2>
            </div>
            <div class="sre-form-actions">
                <button class="sre-button sre-button--secondary" type="button" data-modal-open="users-guest-modal">Crear invitado</button>
                <a class="sre-button" href="{{ route('usuarios.create') }}">Crear usuario</a>
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

        <div class="sre-resource-layout sre-resource-layout--list-wide">
            <section class="sre-panel sre-resource-sidebar" aria-labelledby="usuarios-list-title">
                <div class="sre-panel-heading">
                    <h3 class="sre-panel-title" id="usuarios-list-title">Buscar usuarios</h3>
                </div>

                <form
                    class="sre-filter-form"
                    action="{{ route('usuarios.index') }}"
                    method="get"
                    data-resource-search-form
                    data-resource-type="usuario"
                    data-search-url="{{ route('usuarios.search') }}"
                    data-detail-url="{{ route('usuarios.detail', '__RECORD_ID__') }}"
                >
                    <div class="sre-filter-grid-long">
                        <label class="sre-field">
                            <span class="sre-label">Cédula o código</span>
                            <input class="sre-input" type="search" name="identificacion" placeholder="Escribe cédula o código">
                        </label>
                        <label class="sre-field">
                            <span class="sre-label">Estado</span>
                            <select class="sre-select" name="estado_id">
                                <option value="">Todos</option>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado->id }}">{{ $estado->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="sre-field">
                            <span class="sre-label">Rol</span>
                            <select class="sre-select" name="rol_id">
                                <option value="">Todos</option>
                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="sre-button sre-button--small" type="submit">Aplicar filtros</button>
                    </div>
                </form>

                <div class="sre-panel-content">
                    <div class="sre-record-list" data-resource-results role="list"></div>
                    <div class="sre-empty-state" data-resource-results-status role="status" aria-live="polite">
                        <strong>No hay usuarios para mostrar</strong>
                        <span>Prueba con otros filtros de búsqueda.</span>
                    </div>
                </div>
            </section>

            <section class="sre-panel sre-resource-detail" aria-labelledby="usuario-detail-title">
                <div class="sre-panel-heading">
                    <div>
                        <h3 class="sre-detail-title" id="usuario-detail-title" data-resource-detail-title>Detalle del usuario</h3>
                        <div data-resource-detail-action></div>
                    </div>
                </div>
                <div class="sre-panel-content" data-resource-detail aria-live="polite">
                    <div class="sre-empty-state">
                        <strong>Selecciona un usuario</strong>
                        <span>Su información completa se mostrará en este espacio.</span>
                    </div>
                </div>
            </section>
        </div>

        <dialog class="sre-modal" id="users-guest-modal" aria-labelledby="users-guest-modal-title" @if ($errors->has('nombres') || $errors->has('apellidos') || $errors->has('cedula')) data-validation-errors @endif>
            <section class="sre-modal-card">
                <header class="sre-modal-heading">
                    <div>
                        <p class="sre-eyebrow">Registro rápido</p>
                        <h2 class="sre-modal-title" id="users-guest-modal-title">Crear invitado</h2>
                    </div>
                    <button class="sre-modal-close" type="button" aria-label="Cerrar" data-modal-close>&times;</button>
                </header>
                <form class="sre-modal-form" action="{{ route('usuarios.guest.store') }}" method="POST">
                    @csrf
                    @include('usuarios.partials.guest-fields')
                    <div class="sre-form-actions sre-modal-actions">
                        <button class="sre-button" type="submit">Crear invitado</button>
                        <button class="sre-button sre-button--secondary" type="button" data-modal-close>Cancelar</button>
                    </div>
                </form>
            </section>
        </dialog>
    </section>
@endsection
