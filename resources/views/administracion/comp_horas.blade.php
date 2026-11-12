<section
    class="sre-admin-card"
    id="comp-horas"
    aria-labelledby="comp-horas-title"
    @if (! empty($hoursRefreshUrl))
        data-hours-refresh-url="{{ $hoursRefreshUrl }}"
        data-csrf-token="{{ csrf_token() }}"
        data-refresh-interval="5000"
    @endif
>
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-horas-title">Horas de hoy</h4>
    </header>

    @if ($jugador)
    <div class="sre-admin-card-body sre-admin-card-body--scroll">
        <div class="sre-hours-scroll">
            <table class="sre-hours-table">
                <thead>
                    <tr>
                        <th scope="col">Inicio</th>
                        <th scope="col">Dispositivo</th>
                        <th scope="col">Horas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($historialAsociaciones ?? collect()) as $asociacion)
                        <tr>
                            <td>{{ formatTimeCompact($asociacion->inicio) }}</td>
                            <td title="{{ $asociacion->dispositivo?->nombre ?? 'Sin dispositivo' }}">{{ \Illuminate\Support\Str::limit($asociacion->dispositivo?->nombre ?? 'Sin dispositivo', 18) }}</td>
                            <td>{{ number_format((float) ($asociacion->horas ?? 0), 1) }}h</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="sre-hours-empty" colspan="3">Sin actividad registrada hoy.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sre-hours-total">Total: {{ number_format((float) ($totalHoras ?? 0), 1) }} horas</div>
    </div>
    @endif
</section>
