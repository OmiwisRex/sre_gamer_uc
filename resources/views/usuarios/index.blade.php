@extends('layout')

@section('title', 'Usuarios | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="usuarios-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="usuarios-title">Usuarios</h2>
            </div>
            <a class="sre-button" href="{{ route('usuarios.create') }}">Crear usuario</a>
        </header>

        <div class="sre-resource-layout sre-resource-layout--list-wide">
            <section class="sre-panel sre-resource-sidebar" aria-labelledby="usuarios-list-title">
                <div class="sre-panel-heading">
                    <h3 class="sre-panel-title" id="usuarios-list-title">Buscar usuarios</h3>
                </div>

                <form class="sre-filter-form" action="{{ url('/usuarios') }}" method="get">
                    <div class="sre-filter-grid-long">
                        <label class="sre-field">
                            <span class="sre-label">Cédula o código</span>
                            <input class="sre-input" type="search" name="identificacion" value="{{ request('identificacion') }}" placeholder="Escribe cédula o código">
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
                        <label class="sre-field">
                            <span class="sre-label">Rol</span>
                            <select class="sre-select" name="rol_id">
                                <option value="">Todos</option>
                                @foreach (($roles ?? collect()) as $rol)
                                    <option value="{{ $rol->id }}" @selected((string) request('rol_id') === (string) $rol->id)>{{ $rol->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="sre-button sre-button--small" type="submit">Aplicar filtros</button>
                    </div>
                </form>

                <div class="sre-panel-content">
                    <div class="sre-record-list">
                        @forelse (($usuarios ?? collect()) as $usuario)
                            <article class="sre-record">
                                <a href="{{ url('/usuarios') }}?seleccionado={{ $usuario->id }}" aria-label="Ver detalle de {{ $usuario->nombres }} {{ $usuario->apellidos }}">
                                    <div class="sre-record-top">
                                        <h4 class="sre-record-name">{{ $usuario->nombres }} {{ $usuario->apellidos }}</h4>
                                        <span class="sre-badge">{{ $usuario->estado?->nombre ?? 'Sin estado' }}</span>
                                    </div>
                                    <div class="sre-record-meta">
                                        <span>C.C. {{ $usuario->cedula }}</span>
                                        <span>{{ $usuario->rol?->nombre ?? 'Sin rol' }}</span>
                                    </div>
                                </a>
                                <div class="sre-record-actions">
                                    <a class="sre-button sre-button--secondary sre-button--small" href="{{ url('/usuarios/' . $usuario->id . '/edit') }}">Editar</a>
                                </div>
                            </article>
                        @empty
                            <div class="sre-empty-state">
                                <strong>No hay usuarios para mostrar</strong>
                                <span>Los registros aparecerán aquí cuando estén disponibles.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="sre-panel sre-resource-detail" aria-labelledby="usuario-detail-title">
                @if (isset($usuarioSeleccionado) && $usuarioSeleccionado)
                    <div class="sre-panel-heading">
                        <div>
                            <h3 class="sre-detail-title" id="usuario-detail-title">{{ $usuarioSeleccionado->nombres }} {{ $usuarioSeleccionado->apellidos }}</h3>
                            <p class="sre-detail-subtitle">Información del registro seleccionado</p>
                        </div>
                        <a class="sre-button sre-button--secondary sre-button--small" href="{{ url('/usuarios/' . $usuarioSeleccionado->id . '/edit') }}">Editar</a>
                    </div>
                    <div class="sre-panel-content">
                        <dl class="sre-detail-grid">
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Cédula</dt>
                                <dd class="sre-detail-value">{{ $usuarioSeleccionado->cedula }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Código</dt>
                                <dd class="sre-detail-value">{{ $usuarioSeleccionado->codigo ?: 'No registrado' }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Edad</dt>
                                <dd class="sre-detail-value">{{ $usuarioSeleccionado->year_nace ? now()->year - $usuarioSeleccionado->year_nace : 'No registrada' }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Género</dt>
                                <dd class="sre-detail-value">{{ $usuarioSeleccionado->genero?->nombre ?? 'No registrado' }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Estado</dt>
                                <dd class="sre-detail-value">{{ $usuarioSeleccionado->estado?->nombre ?? 'Sin estado' }}</dd>
                            </div>
                            <div class="sre-detail-item">
                                <dt class="sre-detail-label">Rol</dt>
                                <dd class="sre-detail-value">{{ $usuarioSeleccionado->rol?->nombre ?? 'Sin rol' }}</dd>
                            </div>
                        </dl>
                    </div>
                @else
                    <div class="sre-panel-heading">
                        <h3 class="sre-panel-title" id="usuario-detail-title">Detalle del usuario</h3>
                    </div>
                    <div class="sre-panel-content">
                        <div class="sre-empty-state">
                            <strong>Selecciona un usuario</strong>
                            <span>Su información completa se mostrará en este espacio.</span>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </section>
@endsection
