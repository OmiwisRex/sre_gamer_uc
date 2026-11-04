@extends('layout')

@section('title', 'Editar dispositivo | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="dispositivo-edit-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="dispositivo-edit-title">Editar dispositivo</h2>
            </div>
            <a class="sre-page-back" href="{{ url('/dispositivos') }}">Volver a dispositivos</a>
        </header>

        <form class="sre-form-stack">
            <section class="sre-form-section" aria-labelledby="dispositivo-edit-data-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="dispositivo-edit-data-title">Información del dispositivo</h3>
                    <p class="sre-hint">Los datos disponibles se cargarán al seleccionar el dispositivo.</p>
                </form>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Nombre del dispositivo</span>
                        <input class="sre-input" type="text" name="nombre" value="{{ old('nombre', $dispositivo->nombre ?? '') }}">
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Tipo de dispositivo</span>
                        <select class="sre-select" name="tipo_dispo_id">
                            <option value="">Selecciona un tipo</option>
                            @foreach (($tiposDispo ?? collect()) as $tipoDispo)
                                <option value="{{ $tipoDispo->id }}" @selected((string) old('tipo_dispo_id', $dispositivo->tipo_dispo_id ?? '') === (string) $tipoDispo->id)>{{ $tipoDispo->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Estado</span>
                        <select class="sre-select" name="estado_id">
                            <option value="">Selecciona un estado</option>
                            @foreach (($estados ?? collect()) as $estado)
                                <option value="{{ $estado->id }}" @selected((string) old('estado_id', $dispositivo->estado_id ?? '') === (string) $estado->id)>{{ $estado->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Descripción</span>
                        <textarea class="sre-textarea" name="descripcion">{{ old('descripcion', $dispositivo->descripcion ?? '') }}</textarea>
                    </label>
                </div>
                <div class="sre-form-actions">
                    <button class="sre-button" type="button">Guardar cambios</button>
                </div>
            </section>
        </div>
    </section>
@endsection
