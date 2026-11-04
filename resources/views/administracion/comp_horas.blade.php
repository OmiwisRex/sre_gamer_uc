<section class="sre-admin-card" aria-labelledby="comp-horas-title">
    <header class="sre-admin-card-heading">
        <h4 class="sre-admin-card-title" id="comp-horas-title">Horas de hoy</h4>
    </header>

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
                            <td>{{ \Illuminate\Support\Carbon::parse($asociacion->inicio)->format('H:i') }}</td>
                            <td>{{ $asociacion->dispositivo?->nombre ?? 'Sin dispositivo' }}</td>
                            <td>{{ number_format((float) ($asociacion->horas ?? 0), 1) }}</td>
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
</section>
