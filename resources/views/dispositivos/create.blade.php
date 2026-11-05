@extends('layout')

@section('title', 'Crear dispositivo | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="dispositivo-create-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="dispositivo-create-title">Crear dispositivo</h2>
            </div>
            <a class="sre-page-back" href="{{ url('/dispositivos') }}">Volver a dispositivos</a>
        </header>

        @if (session('success'))
            <p class="sre-badge" role="status">{{ session('success') }}</p>
        @endif

        <form class="sre-form-stack" action="{{ route('dispositivos.store') }}" method="POST">
            @csrf
            <section class="sre-form-section" aria-labelledby="dispositivo-data-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="dispositivo-data-title">Información del dispositivo</h3>
                    <p class="sre-hint">Completa los datos principales del equipo.</p>
                </div>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Nombre del dispositivo</span>
                        <input class="sre-input" type="text" name="nombre" value="{{ old('nombre') }}" maxlength="24" pattern=".*\S.*" title="Ingresa al menos un carácter que no sea un espacio." placeholder="Ej. PC Gamer 01" required>
                        @error('nombre')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Tipo de dispositivo</span>
                        <select class="sre-select" name="tipo_dispo_id" required>
                            <option value="">Selecciona un tipo</option>
                            @foreach (($tiposDispo ?? collect()) as $tipoDispo)
                                <option value="{{ $tipoDispo->id }}" @selected((string) old('tipo_dispo_id') === (string) $tipoDispo->id)>{{ $tipoDispo->nombre }}</option>
                            @endforeach
                        </select>
                        @error('tipo_dispo_id')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Descripción <span class="sre-hint">(opcional)</span></span>
                        <textarea class="sre-textarea" name="descripcion" maxlength="256" placeholder="Describe el equipo o sus características">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
                <div class="sre-form-actions">
                    <button class="sre-button" type="submit">Crear dispositivo</button>
                </div>
            </section>
        </form>
    </section>
@endsection
