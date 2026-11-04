@extends('layout')

@section('title', 'Editar evento | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="evento-edit-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="evento-edit-title">Editar evento</h2>
            </div>
            <a class="sre-page-back" href="{{ url('/eventos') }}">Volver a eventos</a>
        </header>

        <form class="sre-form-stack">
            <section class="sre-form-section" aria-labelledby="evento-edit-data-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-edit-data-title">Información del evento</h3>
                    <p class="sre-hint">Los datos disponibles se cargarán al seleccionar el evento.</p>
                </form>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Nombre del evento</span>
                        <input class="sre-input" type="text" name="nombre" value="{{ old('nombre', $evento->nombre ?? '') }}">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Cédula del organizador</span>
                        <input class="sre-input" type="text" name="organizador_cedula" value="{{ old('organizador_cedula', $evento->usuario?->cedula ?? '') }}" inputmode="numeric">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Descripción</span>
                        <textarea class="sre-textarea" name="descripcion">{{ old('descripcion', $evento->descripcion ?? '') }}</textarea>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Fecha y hora de inicio</span>
                        <input class="sre-input" type="datetime-local" name="inicio" value="{{ old('inicio', isset($evento->inicio) ? \Illuminate\Support\Carbon::parse($evento->inicio)->format('Y-m-d\TH:i') : '') }}">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Duración (horas)</span>
                        <input class="sre-input" type="number" name="duracion_horas" min="1" step="1" value="{{ old('duracion_horas', $evento->duracion_horas ?? '') }}">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Aforo aproximado</span>
                        <input class="sre-input" type="number" name="aforo" min="1" step="1" value="{{ old('aforo', $evento->aforo ?? '') }}">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Estado</span>
                        <select class="sre-select" name="estado_id">
                            <option value="">Selecciona un estado</option>
                            @foreach (($estados ?? collect()) as $estado)
                                <option value="{{ $estado->id }}" @selected((string) old('estado_id', $evento->estado_id ?? '') === (string) $estado->id)>{{ $estado->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Administración del evento</span>
                        <select class="sre-select" name="auto_admin">
                            <option value="1" @selected((string) old('auto_admin', $evento->auto_admin ?? '') === '1')>Auto administrado</option>
                            <option value="0" @selected((string) old('auto_admin', $evento->auto_admin ?? '') === '0')>Administrado por encargados</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="sre-form-section" aria-labelledby="evento-edit-devices-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-edit-devices-title">Dispositivos vinculados</h3>
                    <p class="sre-hint">Marca los equipos reservados para este evento.</p>
                </div>
                <div class="sre-check-list">
                    @forelse (($dispositivos ?? collect()) as $dispositivo)
                        <label class="sre-check-option">
                            <input
                                type="checkbox"
                                name="dispositivos[]"
                                value="{{ $dispositivo->id }}"
                                @checked(collect(old('dispositivos', isset($evento) ? $evento->dispositivos->modelKeys() : []))->contains($dispositivo->id))
                            >
                            <span>{{ $dispositivo->nombre }} <span class="sre-hint">({{ $dispositivo->tipoDispo?->nombre ?? 'Sin tipo' }})</span></span>
                        </label>
                    @empty
                        <p class="sre-hint">Los dispositivos disponibles aparecerán aquí.</p>
                    @endforelse
                </div>
                <div class="sre-form-actions">
                    <button class="sre-button" type="button">Guardar cambios</button>
                </div>
            </section>
        </div>
    </section>
@endsection
