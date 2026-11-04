<section class="sre-admin-module sre-admin-module--schedule" aria-labelledby="mod-horarios-title">
    <header class="sre-admin-module-heading">
        <div>
            <h3 class="sre-admin-module-title" id="mod-horarios-title">Horarios</h3>
        </div>
    </header>

    <div class="sre-schedule-layout">
        <div>
            @include('administracion.comp_tiempos', [
                'fechaSeleccionada' => $fechaSeleccionada ?? null,
                'dispositivos' => $dispositivos ?? collect(),
                'horasTimeline' => $horasTimeline ?? range(6, 12),
                'ocupaciones' => $ocupaciones ?? [],
            ])
        </div>

        <div>
            @include('administracion.comp_seleccion', [
                'seleccionItems' => $seleccionItems ?? collect(),
            ])
        </div>
    </div>
</section>
