@extends('layout')

@section('title', 'Crear usuario | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="usuario-create-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="usuario-create-title">Crear usuario</h2>
            </div>
            <a class="sre-page-back" href="{{ url('/usuarios') }}">Volver a usuarios</a>
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

        <form class="sre-form-stack" action="{{ route('usuarios.guest.store') }}" method="POST" data-user-registration>
            @csrf
            <section class="sre-form-section" aria-labelledby="usuario-basic-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="usuario-basic-title">Información básica</h3>
                    <p class="sre-hint">Estos datos son suficientes para registrar a un invitado. También se usarán para el usuario de la comunidad.</p>
                </div>
                <div class="sre-filter-grid-long">
                    @include('usuarios.partials.guest-fields')
                    <button class="sre-button" type="submit" data-user-type="guest">Crear invitado</button>
                </div>
            </section>

            <section class="sre-form-section" aria-labelledby="usuario-community-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="usuario-community-title">Datos de usuario de la comunidad</h3>
                    <p class="sre-hint">Completa esta información para registrar al usuario con acceso por correo.</p>
                </div>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Año de nacimiento</span>
                        <input class="sre-input" type="number" name="year_nace" value="{{ old('year_nace') }}" min="1900" max="{{ now()->year }}" step="1" placeholder="AAAA" data-community-required>
                        @error('year_nace')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Género</span>
                        <select class="sre-select" name="genero_id" data-community-required>
                            <option value="">Selecciona un género</option>
                            @foreach (($generos ?? collect()) as $genero)
                                <option value="{{ $genero->id }}" @selected((string) old('genero_id') === (string) $genero->id)>{{ $genero->nombre }}</option>
                            @endforeach
                        </select>
                        @error('genero_id')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Código de estudiante (opcional)</span>
                        <input class="sre-input" type="text" name="codigo" value="{{ old('codigo') }}" maxlength="24" placeholder="Código institucional">
                        @error('codigo')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Rol</span>
                        <select class="sre-select" name="rol_id" data-community-required>
                            @foreach (($roles ?? collect()) as $rol)
                                <option value="{{ $rol->id }}" @selected((string) old('rol_id', $roles->firstWhere('nombre', 'Estudiante')?->id) === (string) $rol->id)>{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                        @error('rol_id')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Correo electrónico</span>
                        <input class="sre-input" type="email" name="email" value="{{ old('email') }}" maxlength="128" autocomplete="email" placeholder="nombre@unicatolica.edu.co" data-community-required>
                        <span class="sre-hint">Se enviará una contraseña inicial al correo registrado.</span>
                        @error('email')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
                <div class="sre-form-grid">
                    <button class="sre-button" type="submit" formaction="{{ route('usuarios.community.store') }}" data-user-type="community">Crear usuario de la comunidad</button>
                </div>
            </section>
        </form>
    </section>
@endsection
