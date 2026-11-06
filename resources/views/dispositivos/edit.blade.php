@extends('layout')

@section('title', 'Editar dispositivo | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="dispositivo-edit-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="dispositivo-edit-title">Editar dispositivo</h2>
            </div>
            <a class="sre-page-back" href="{{ route('dispositivos.index') }}">Volver a dispositivos</a>
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

        <form class="sre-form-stack" action="{{ route('dispositivos.update', $dispositivo) }}" method="POST">
            @csrf
            @method('PUT')
            <section class="sre-form-section" aria-labelledby="dispositivo-edit-data-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="dispositivo-edit-data-title">Información del dispositivo</h3>
                    <p class="sre-hint">Actualiza el nombre, tipo, estado y descripción del equipo.</p>
                </div>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Nombre del dispositivo</span>
                        <input class="sre-input" type="text" name="nombre" value="{{ old('nombre', $dispositivo->nombre) }}" maxlength="24" required>
                        @error('nombre')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Tipo de dispositivo</span>
                        <select class="sre-select" name="tipo_dispo_id" required>
                            <option value="">Selecciona un tipo</option>
                            @foreach ($tiposDispo as $tipoDispo)
                                <option value="{{ $tipoDispo->id }}" @selected((string) old('tipo_dispo_id', $dispositivo->tipo_dispo_id) === (string) $tipoDispo->id)>{{ $tipoDispo->nombre }}</option>
                            @endforeach
                        </select>
                        @error('tipo_dispo_id')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Estado</span>
                        <select class="sre-select" name="estado_id" required>
                            <option value="">Selecciona un estado</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado->id }}" @selected((string) old('estado_id', $dispositivo->estado_id) === (string) $estado->id)>{{ $estado->nombre }}</option>
                            @endforeach
                        </select>
                        @error('estado_id')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label></label>
                    <label class="sre-field">
                        <span class="sre-label">Descripción</span>
                        <textarea class="sre-textarea" name="descripcion" maxlength="256">{{ old('descripcion', $dispositivo->descripcion) }}</textarea>
                        @error('descripcion')
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
