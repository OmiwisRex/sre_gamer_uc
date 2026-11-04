@extends('layout')

@section('title', 'Configuración | SGUC')

@section('content')
    <section class="sre-page sre-settings-page" aria-labelledby="configuracion-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="configuracion-title">Configuración</h2>
            </div>
        </header>

        <div class="sre-settings-content">
            <section class="sre-form-section" aria-labelledby="password-change-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="password-change-title">Cambio de contraseña</h3>
                    <p class="sre-hint">Confirma tu contraseña actual y escribe dos veces la nueva.</p>
                </div>

                <form class="sre-settings-form">
                    <label class="sre-field">
                        <span class="sre-label">Contraseña actual</span>
                        <input class="sre-input" type="password" name="current_password" autocomplete="current-password" placeholder="Contraseña actual">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Nueva contraseña</span>
                        <input class="sre-input" type="password" name="new_password" autocomplete="new-password" placeholder="Nueva contraseña">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Confirmar nueva contraseña</span>
                        <input class="sre-input" type="password" name="new_password_confirmation" autocomplete="new-password" placeholder="Repite la nueva contraseña">
                    </label>
                    <div class="sre-form-actions">
                        <button class="sre-button" type="button">Actualizar contraseña</button>
                    </div>
                </form>
            </section>
        </div>
    </section>
@endsection