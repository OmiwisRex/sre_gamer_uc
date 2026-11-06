@extends('layout')

@section('title', 'Editar evento | SGUC')

@section('content')
    <section class="sre-page" aria-labelledby="evento-edit-title">
        <header class="sre-page-header">
            <div class="sre-page-heading">
                <h2 class="sre-page-title" id="evento-edit-title">Editar evento</h2>
            </div>
            <a class="sre-page-back" href="{{ route('eventos.index') }}">Volver a eventos</a>
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

        <form class="sre-form-stack" action="{{ route('eventos.update', $evento) }}" method="POST">
            @csrf
            @method('PUT')
            <section class="sre-form-section" aria-labelledby="evento-edit-data-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-edit-data-title">Información del evento</h3>
                    <p class="sre-hint">Actualiza los datos, programación y estado del evento.</p>
                </div>
                <div class="sre-form-grid">
                    <label class="sre-field">
                        <span class="sre-label">Nombre del evento</span>
                        <input class="sre-input" type="text" name="nombre" value="{{ old('nombre', $evento->nombre) }}" maxlength="32" required>
                        @error('nombre')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Cédula del organizador</span>
                        <input class="sre-input" type="text" name="organizador_cedula" value="{{ old('organizador_cedula', $evento->usuario?->cedula) }}" maxlength="24" inputmode="numeric" required>
                        @error('organizador_cedula')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Fecha y hora de inicio</span>
                        <input class="sre-input" type="datetime-local" name="inicio" value="{{ old('inicio', \Illuminate\Support\Carbon::parse($evento->inicio)->format('Y-m-d\TH:i')) }}" required>
                        @error('inicio')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Duración (horas)</span>
                        <input class="sre-input" type="number" name="duracion_horas" value="{{ old('duracion_horas', $evento->duracion_horas) }}" min="1" max="24" step="1" required>
                        @error('duracion_horas')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Aforo aproximado</span>
                        <input class="sre-input" type="number" name="aforo" value="{{ old('aforo', $evento->aforo) }}" min="1" step="1" required>
                        @error('aforo')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Estado</span>
                        <select class="sre-select" name="estado_id" required>
                            <option value="">Selecciona un estado</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado->id }}" @selected((string) old('estado_id', $evento->estado_id) === (string) $estado->id)>{{ $estado->nombre }}</option>
                            @endforeach
                        </select>
                        @error('estado_id')
                            <span class="sre-hint">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="sre-field">
                        <span class="sre-label">Administración del evento</span>
                        <select class="sre-select" name="auto_admin" required>
                            <option value="1" @selected((string) old('auto_admin', $evento->auto_admin) === '1')>Auto administrado</option>
                            <option value="0" @selected((string) old('auto_admin', $evento->auto_admin) === '0')>Administrado por encargados</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="sre-form-section" aria-labelledby="evento-description-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-description-title">Descripción <span class="sre-hint">(opcional)</span></h3>
                </div>
                <textarea class="sre-textarea" name="descripcion" maxlength="256">{{ old('descripcion', $evento->descripcion) }}</textarea>
                @error('descripcion')
                    <span class="sre-hint">{{ $message }}</span>
                @enderror
            </section>

            <section class="sre-form-section" aria-labelledby="evento-edit-devices-title">
                <div class="sre-form-section-heading">
                    <h3 class="sre-form-section-title" id="evento-edit-devices-title">Dispositivos vinculados</h3>
                    <p class="sre-hint">Marca los equipos reservados para este evento.</p>
                </div>
                <div class="sre-check-list">
                    @forelse ($dispositivos as $dispositivo)
                        <label class="sre-check-option">
                            <input
                                type="checkbox"
                                name="dispositivos[]"
                                value="{{ $dispositivo->id }}"
                                @checked(collect(old('dispositivos', $evento->dispositivos->modelKeys()))->contains(fn ($id) => (int) $id === $dispositivo->id))
                            >
                            <span>{{ $dispositivo->nombre }} <span class="sre-hint">({{ $dispositivo->tipoDispo?->nombre ?? 'Sin tipo' }})</span></span>
                        </label>
                    @empty
                        <p class="sre-hint">No hay dispositivos disponibles.</p>
                    @endforelse
                </div>
                <div class="sre-form-actions">
                    <button class="sre-button" type="submit">Guardar cambios</button>
                </div>
            </section>
        </form>
    </section>
@endsection
