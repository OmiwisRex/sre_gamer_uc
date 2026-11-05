<section class="sre-admin-card sre-search-card" aria-labelledby="comp-busqueda-title">
    <div class="sre-search-layout">
        
        <div class="sre-search-pane">
            <header class="sre-admin-card-heading">
                <h4 class="sre-admin-card-title" id="comp-busqueda-title">Búsqueda</h4>
                <button class="sre-button sre-button--secondary" type="button" data-modal-open="guest-modal">
                    Crear invitado
                </button>
            </header>

            <div class="sre-admin-card-body">
                <form class="sre-search-form" role="search">
                    <label class="sre-field">
                        <span class="sre-label">Cédula / código</span>
                        <input
                            class="sre-input"
                            type="search"
                            name="jugador_id"
                            value="{{ old('jugador_id', isset($jugador) ? $jugador->cedula : '') }}"
                            placeholder="Número de cédula o código"
                        >
                    </label>
                    <button class="sre-button" type="button">Buscar jugador</button>
                </form>
            </div>
        </div>

        <section class="sre-search-result" aria-labelledby="search-result-title">
            <header class="sre-admin-card-heading">
                <h5 class="sre-admin-card-title" id="search-result-title">Resultado</h5>
                @if (isset($jugador) && $jugador)
                    <span class="sre-badge">{{ $jugador->estado?->nombre ?? 'Encontrado' }}</span>
                @else
                    <span class="sre-badge sre-badge--neutral">Sin consulta</span>
                @endif
            </header>
            <div class="sre-admin-card-body">
                @if (isset($jugador) && $jugador)
                    <div class="sre-player-found">
                        <p class="sre-player-found-name">{{ $jugador->nombres }} {{ $jugador->apellidos }}</p>
                        <span class="sre-hint">C.C. {{ $jugador->cedula }}</span>
                        <span class="sre-badge">{{ $jugador->rol?->nombre ?? 'Sin rol' }}</span>
                    </div>
                @else
                    <div class="sre-empty-state">
                        <strong>Sin jugador seleccionado</strong>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <dialog class="sre-modal" id="guest-modal" aria-labelledby="guest-modal-title" @if ($errors->has('nombres') || $errors->has('apellidos') || $errors->has('cedula')) data-validation-errors @endif>
        <section class="sre-modal-card">
            <header class="sre-modal-heading">
                <div>
                    <p class="sre-eyebrow">Registro rápido</p>
                    <h2 class="sre-modal-title" id="guest-modal-title">Crear invitado</h2>
                </div>
                <button class="sre-modal-close" type="button" aria-label="Cerrar" data-modal-close>&times;</button>
            </header>

            <form class="sre-modal-form" action="{{ route('usuarios.guest.store') }}" method="POST">
                @csrf
                @include('usuarios.partials.guest-fields')
                <div class="sre-form-actions sre-modal-actions">
                    <button class="sre-button" type="submit">Crear invitado</button>
                    <button class="sre-button sre-button--secondary" type="button" data-modal-close>Cancelar</button>
                </div>
            </form>
        </section>
    </dialog>
</section>
