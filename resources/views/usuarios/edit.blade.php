@extends('layout')

@section('title', 'Editar usuario | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="usuario-edit-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="usuario-edit-title">Editar usuario</h2>
            </div>
            <a class="sre-page-back" href="{{ url('/usuarios') }}">Volver a usuarios</a>
        </header>

        <form class="sre-form-stack">
            <section class="sre-form-section" aria-labelledby="usuario-edit-basic-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="usuario-edit-basic-title">Información básica</h3>
                    <p class="sre-hint">Los datos disponibles se cargarán al seleccionar el usuario.</p>
                </form>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Nombres</span>
                        <input class="sre-input" type="text" name="nombres" value="{{ old('nombres', $usuario->nombres ?? '') }}" autocomplete="given-name">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Apellidos</span>
                        <input class="sre-input" type="text" name="apellidos" value="{{ old('apellidos', $usuario->apellidos ?? '') }}" autocomplete="family-name">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Cédula</span>
                        <input class="sre-input" type="text" name="cedula" value="{{ old('cedula', $usuario->cedula ?? '') }}" inputmode="numeric">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Estado</span>
                        <select class="sre-select" name="estado_id">
                            <option value="">Selecciona un estado</option>
                            @foreach (($estados ?? collect()) as $estado)
                                <option value="{{ $estado->id }}" @selected((string) old('estado_id', $usuario->estado_id ?? '') === (string) $estado->id)>{{ $estado->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </section>

            <section class="sre-form-section" aria-labelledby="usuario-edit-community-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="usuario-edit-community-title">Datos de comunidad y acceso</h3>
                    <p class="sre-hint">Completa los datos adicionales para convertir un invitado en usuario de la comunidad.</p>
                </div>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Año de nacimiento</span>
                        <input class="sre-input" type="number" name="year_nace" min="1900" max="{{ now()->year }}" value="{{ old('year_nace', $usuario->year_nace ?? '') }}" placeholder="AAAA">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Género</span>
                        <select class="sre-select" name="genero_id">
                            <option value="">Selecciona un género</option>
                            @foreach (($generos ?? collect()) as $genero)
                                <option value="{{ $genero->id }}" @selected((string) old('genero_id', $usuario->genero_id ?? '') === (string) $genero->id)>{{ $genero->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Código de estudiante <span class="sre-hint">(opcional)</span></span>
                        <input class="sre-input" type="text" name="codigo" value="{{ old('codigo', $usuario->codigo ?? '') }}">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Rol</span>
                        <select class="sre-select" name="rol_id">
                            <option value="">Selecciona un rol</option>
                            @foreach (($roles ?? collect()) as $rol)
                                <option value="{{ $rol->id }}" @selected((string) old('rol_id', $usuario->rol_id ?? '') === (string) $rol->id)>{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Correo electrónico</span>
                        <input class="sre-input" type="email" name="email" value="{{ old('email', isset($usuario) ? $usuario->sesion?->email : '') }}" autocomplete="email" placeholder="nombre@dominio.com">
                        <span class="sre-hint">Si se agrega por primera vez, se enviará una contraseña inicial a este correo.</span>
                    </label>
                </div>
                <div class="sre-form-actions">
                    <button class="sre-button" type="button">Guardar cambios</button>
                </div>
            </section>
        </div>
    </section>
@endsection
