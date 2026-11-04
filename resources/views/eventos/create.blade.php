@extends('layout')

@section('title', 'Crear evento | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="evento-create-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="evento-create-title">Crear evento</h2>
            </div>
            <a class="sre-page-back" href="{{ url('/eventos') }}">Volver a eventos</a>
        </header>

        <form class="sre-form-stack">
            <section class="sre-form-section" aria-labelledby="evento-data-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-data-title">Información del evento</h3>
                    <p class="sre-hint">Registra los datos principales y el modo de administración.</p>
                </div>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Nombre del evento</span>
                        <input class="sre-input" type="text" name="nombre" placeholder="Nombre">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Cédula del organizador</span>
                        <input class="sre-input" type="text" name="organizador_cedula" inputmode="numeric" placeholder="Identificación del usuario responsable">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Fecha y hora de inicio</span>
                        <input class="sre-input" type="datetime-local" name="inicio">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Duración (horas)</span>
                        <input class="sre-input" type="number" name="duracion_horas" min="1" step="1" placeholder="Horas">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Aforo aproximado</span>
                        <input class="sre-input" type="number" name="aforo" min="1" step="1" placeholder="Cantidad de invitados">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Administración del evento</span>
                        <select class="sre-select" name="auto_admin">
                            <option value="">Selecciona una opción</option>
                            <option value="1">Auto administrado</option>
                            <option value="0">Los jugadores reservan</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="sre-form-section" aria-labelledby="evento-devices-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-devices-title">Descripción</h3>
                </div>
                <textarea class="sre-textarea" name="descripcion" placeholder="Describe el evento"></textarea>
            </section>

            <section class="sre-form-section" aria-labelledby="evento-devices-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-devices-title">Dispositivos vinculados</h3>
                    <p class="sre-hint">Selecciona los equipos que quedarán reservados para el evento.</p>
                </div>
                <div class="sre-check-list">
                    @forelse (($dispositivos ?? collect()) as $dispositivo)
                        <label class="sre-check-option">
                            <input type="checkbox" name="dispositivos[]" value="{{ $dispositivo->id }}">
                            <span>{{ $dispositivo->nombre }} <span class="sre-hint">({{ $dispositivo->tipoDispo?->nombre ?? 'Sin tipo' }})</span></span>
                        </label>
                    @empty
                        <p class="sre-hint">Los dispositivos disponibles aparecerán aquí.</p>
                    @endforelse
                </div>
                <div class="sre-form-actions">
                    <button class="sre-button" type="button">Crear evento</button>
                </div>
            </section>
        </form>
    </section>
@endsection
