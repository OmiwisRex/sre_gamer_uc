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

        @if (session('success'))
            <p class="sre-badge" role="status">{{ session('success') }}</p>
        @endif

        <form class="sre-form-stack" action="{{ route('eventos.store') }}" method="POST">
            @csrf
            @if ($errors->any())
                <ul class="sre-hint" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            <section class="sre-form-section" aria-labelledby="evento-data-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-data-title">Información del evento</h3>
                    <p class="sre-hint">Registra los datos principales y el modo de administración.</p>
                </div>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Nombre del evento</span>
                        <input class="sre-input" type="text" name="nombre" value="{{ old('nombre') }}" maxlength="32" pattern=".*\S.*" title="Ingresa al menos un carácter que no sea un espacio." placeholder="Nombre" required>
                        @error('nombre')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Cédula del organizador</span>
                        <input class="sre-input" type="text" name="organizador_cedula" value="{{ old('organizador_cedula') }}" inputmode="numeric" placeholder="Identificación del usuario responsable" required>
                        @error('organizador_cedula')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Fecha y hora de inicio</span>
                        <input class="sre-input" type="datetime-local" name="inicio" value="{{ old('inicio', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('inicio')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Duración (horas)</span>
                        <input class="sre-input" type="number" name="duracion_horas" value="{{ old('duracion_horas') }}" min="1" max="24" step="1" placeholder="Horas" required>
                        @error('duracion_horas')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Aforo aproximado</span>
                        <input class="sre-input" type="number" name="aforo" value="{{ old('aforo') }}" min="1" step="1" placeholder="Cantidad de invitados" required>
                        @error('aforo')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Administración del evento</span>
                        <select class="sre-select" name="auto_admin">
                            <option value="1" @selected(old('auto_admin', '1') === '1')>Auto administrado</option>
                            <option value="0" @selected(old('auto_admin', '1') === '0')>Los jugadores reservan</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="sre-form-section" aria-labelledby="evento-description-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-description-title">Descripción <span class="sre-hint">(opcional)</span></h3>
                </div>
                <textarea class="sre-textarea" name="descripcion" maxlength="256" placeholder="Describe el evento">{{ old('descripcion') }}</textarea>
                @error('descripcion')
                    <span class="sre-hint">{{ $message }}</span>
                @enderror
            </section>

            <section class="sre-form-section" aria-labelledby="evento-linked-devices-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-linked-devices-title">Dispositivos vinculados</h3>
                    <p class="sre-hint">Selecciona los equipos que quedarán reservados para el evento.</p>
                </div>
                <div class="sre-check-list">
                    @forelse (($dispositivos ?? collect()) as $dispositivo)
                        <label class="sre-check-option">
                            <input type="checkbox" name="dispositivos[]" value="{{ $dispositivo->id }}" @checked(collect(old('dispositivos', []))->contains((string) $dispositivo->id))>
                            <span>{{ $dispositivo->nombre }} <span class="sre-hint">({{ $dispositivo->tipoDispo?->nombre ?? 'Sin tipo' }})</span></span>
                        </label>
                    @empty
                        <p class="sre-hint">Los dispositivos disponibles aparecerán aquí.</p>
                    @endforelse
                </div>
                @error('dispositivos')
                    <span class="sre-hint">{{ $message }}</span>
                @enderror
                <div class="sre-form-actions">
                    <button class="sre-button" type="submit">Crear evento</button>
                </div>
            </section>
        </form>
    </section>
@endsection
