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

        <form class="sre-form-stack">
            <section class="sre-form-section" aria-labelledby="usuario-basic-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="usuario-basic-title">Información básica</h3>
                    <p class="sre-hint">Estos datos son suficientes para registrar a un invitado.</p>
                </div>
                <div class="sre-filter-grid-long">
                    <label class="sre-field">
                        <span class="sre-label">Nombres</span>
                        <input class="sre-input" type="text" name="nombres" autocomplete="given-name" placeholder="Nombres">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Apellidos</span>
                        <input class="sre-input" type="text" name="apellidos" autocomplete="family-name" placeholder="Apellidos">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Cédula</span>
                        <input class="sre-input" type="text" name="cedula" inputmode="numeric" placeholder="Número de identificación">
                    </label>
                    <button class="sre-button" type="button">Crear invitado</button>
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
                        <input class="sre-input" type="number" name="year_nace" min="1900" max="{{ now()->year }}" placeholder="AAAA">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Género</span>
                        <select class="sre-select" name="genero_id">
                            <option value="">Selecciona un género</option>
                            @foreach (($generos ?? collect()) as $genero)
                                <option value="{{ $genero->id }}">{{ $genero->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Código de estudiante <span class="sre-hint">(opcional)</span></span>
                        <input class="sre-input" type="text" name="codigo" placeholder="Código institucional">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Rol</span>
                        <select class="sre-select" name="rol_id">
                            <option value="">Selecciona un rol</option>
                            @foreach (($roles ?? collect()) as $rol)
                                <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Correo electrónico</span>
                        <input class="sre-input" type="email" name="email" autocomplete="email" placeholder="nombre@unicatolica.edu.co">
                        <span class="sre-hint">Se enviará una contraseña inicial al correo registrado.</span>
                    </label>
                </div>
                <div class="sre-form-grid">
                    <button class="sre-button" type="button">Crear usuario de la comunidad</button>
                </div>
            </section>
        </form>
    </section>
@endsection
