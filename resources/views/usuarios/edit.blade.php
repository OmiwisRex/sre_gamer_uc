@extends('layout')

@section('title', 'Editar usuario | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="usuario-edit-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="usuario-edit-title">Editar usuario</h2>
            </div>
            <a class="sre-page-back" href="{{ route('usuarios.index') }}">Volver a usuarios</a>
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

        <form class="sre-form-stack" action="{{ route('usuarios.update', $usuario) }}" method="POST">
            @csrf
            @method('PUT')
            <section class="sre-form-section" aria-labelledby="usuario-edit-basic-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="usuario-edit-basic-title">Información básica</h3>
                    <p class="sre-hint">Actualiza los datos personales y el estado del usuario.</p>
                </div>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Nombres</span>
                        <input class="sre-input" type="text" name="nombres" value="{{ old('nombres', $usuario->nombres) }}" maxlength="32" autocomplete="given-name" required>
                        @error('nombres')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Apellidos</span>
                        <input class="sre-input" type="text" name="apellidos" value="{{ old('apellidos', $usuario->apellidos) }}" maxlength="32" autocomplete="family-name" required>
                        @error('apellidos')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Cédula</span>
                        <input class="sre-input" type="text" name="cedula" value="{{ old('cedula', $usuario->cedula) }}" maxlength="24" inputmode="numeric" required>
                        @error('cedula')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Estado</span>
                        <select class="sre-select" name="estado_id" required>
                            <option value="">Selecciona un estado</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado->id }}" @selected((string) old('estado_id', $usuario->estado_id) === (string) $estado->id)>{{ $estado->nombre }}</option>
                            @endforeach
                        </select>
                        @error('estado_id')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
            </section>

            <section class="sre-form-section" aria-labelledby="usuario-edit-community-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="usuario-edit-community-title">Datos de comunidad y acceso</h3>
                    <p class="sre-hint">Completa estos campos para convertir un invitado en usuario de la comunidad.</p>
                </div>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Año de nacimiento</span>
                        <input class="sre-input" type="number" name="year_nace" min="1900" max="{{ now()->year }}" value="{{ old('year_nace', $isGuest ? '' : $usuario->year_nace) }}" placeholder="AAAA" data-user-edit-community>
                        @error('year_nace')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Género</span>
                        <select class="sre-select" name="genero_id" data-user-edit-community>
                            <option value="">Selecciona un género</option>
                            @foreach ($generos as $genero)
                                <option value="{{ $genero->id }}" @selected((string) old('genero_id', $isGuest ? '' : $usuario->genero_id) === (string) $genero->id)>{{ $genero->nombre }}</option>
                            @endforeach
                        </select>
                        @error('genero_id')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    @if ($codigoEditable)
                        <label class="sre-field">
                            <span class="sre-label">Código de estudiante <span class="sre-hint">(opcional)</span></span>
                            <input class="sre-input" type="text" name="codigo" value="{{ old('codigo', $isGuest ? '' : $usuario->codigo) }}" maxlength="24">
                            @error('codigo')
                                <span class="sre-hint">{{ $message }}</span>
                            @enderror
                        </label>
                    @endif
                    <label class="sre-field">
                        <span class="sre-label">Rol</span>
                        <select class="sre-select" name="rol_id" data-user-edit-role>
                            @if ($isGuest)
                                <option value="">Mantener como invitado</option>
                            @endif
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->id }}" @selected((string) old('rol_id', $isGuest ? '' : $usuario->rol_id) === (string) $rol->id)>{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                        @error('rol_id')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Correo electrónico</span>
                        <input class="sre-input" type="email" name="email" value="{{ old('email', $isGuest ? '' : $usuario->sesion?->email) }}" maxlength="128" autocomplete="email" placeholder="nombre@dominio.com" data-user-edit-community>
                        <span class="sre-hint">Al convertir un invitado, se enviará una contraseña inicial a este correo.</span>
                        @error('email')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
                <div class="sre-form-actions">
                    <button class="sre-button" type="submit">Guardar cambios</button>
                </div>
            </section>
        </form>
    </section>
@endsection
