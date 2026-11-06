@extends('layout')

@section('title', 'Configuración | SGUC')

@section('content')
    <section class="sre-page sre-settings-page" aria-labelledby="configuracion-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="configuracion-title">Configuración</h2>
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

        <div class="sre-settings-content">
            <section class="sre-form-section" aria-labelledby="password-change-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="password-change-title">Cambio de contraseña</h3>
                    <p class="sre-hint">Confirma tu contraseña actual y escribe dos veces la nueva.</p>
                </div>

                <form class="sre-settings-form" action="{{ route('configuracion.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <label class="sre-field">
                        <span class="sre-label">Contraseña actual</span>
                        <input class="sre-input" type="password" name="current_password" autocomplete="current-password" placeholder="Contraseña actual" required>
                        @error('current_password')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Nueva contraseña</span>
                        <input class="sre-input" type="password" name="new_password" autocomplete="new-password" placeholder="Nueva contraseña" minlength="6" required>
                        @error('new_password')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Confirmar nueva contraseña</span>
                        <input class="sre-input" type="password" name="new_password_confirmation" autocomplete="new-password" placeholder="Repite la nueva contraseña" minlength="6" required>
                    </label>
                    <div class="sre-form-actions">
                        <button class="sre-button" type="submit">Actualizar contraseña</button>
                    </div>
                </form>
            </section>
        </div>
    </section>
@endsection