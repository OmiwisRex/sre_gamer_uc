document.addEventListener('DOMContentLoaded', () => {
    let scheduleUpdateInProgress = false;
    let scheduleUpdateTimer = null;

    const updateSchedule = async () => {
        const tiemposPanel = document.querySelector('#comp-tiempos');
        const fechaInput = document.querySelector('.sre-fecha-horario-input');

        if (!(tiemposPanel instanceof HTMLElement) || !(fechaInput instanceof HTMLInputElement)) {
            if (scheduleUpdateTimer) {
                window.clearInterval(scheduleUpdateTimer);
            }
            return;
        }

        if (scheduleUpdateInProgress) {
            return;
        }

        scheduleUpdateInProgress = true;
        try {
            const endpoint = fechaInput.dataset.endpoint;
            const fecha = fechaInput.value;

            const response = await fetch(`${endpoint}?fecha=${fecha}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`Error al actualizar horario (${response.status}).`);
            }

            const data = await response.json();
            renderSchedule(data, tiemposPanel, fechaInput);
        } catch (error) {
            console.error('Error updating schedule:', error);
        } finally {
            scheduleUpdateInProgress = false;
        }
    };

    const renderSchedule = (data, tiemposPanel, fechaInput) => {
        const grid = tiemposPanel.querySelector('.sre-timeline-grid');
        if (!grid) return;

        const hoy = new Date().toISOString().split('T')[0];
        const esHoy = fechaInput.value === hoy;
        const horaActual = new Date().getHours();

        let html = '<span class="sre-timeline-cell sre-timeline-cell--heading" role="columnheader">Dispositivo</span>';

        // Header with hours
        for (let hora of data.horasTimeline) {
            const clases = ['sre-timeline-cell', 'sre-timeline-cell--heading'];
            if (esHoy && hora === horaActual) {
                clases.push('sre-timeline-cell--now');
            }
            html += `<span class="${clases.join(' ')}" role="columnheader">${String(hora).padStart(2, '0')}:00</span>`;
        }

        // Rows for each device
        for (let dispositivo of data.dispositivos) {
            html += `<span class="sre-timeline-cell sre-timeline-cell--device" role="rowheader" title="${dispositivo.nombre}">${dispositivo.nombre}</span>`;

            for (let hora of data.horasTimeline) {
                const ocupacion = data.ocupaciones[dispositivo.id]?.[hora];

                if (ocupacion) {
                    html += `<button
                        class="sre-timeline-cell sre-timeline-cell--${ocupacion.tipo}"
                        type="button"
                        role="button"
                        data-dispositivo-id="${dispositivo.id}"
                        data-hora="${hora}"
                        data-fecha="${fechaInput.value}"
                        title="${ocupacion.etiqueta}"
                    >${ocupacion.etiqueta.substring(0, 15)}</button>`;
                } else {
                    html += `<span class="sre-timeline-cell" role="cell"></span>`;
                }
            }
        }

        grid.innerHTML = html;
        attachScheduleClickHandlers(fechaInput.value);
    };

    const attachScheduleClickHandlers = (fecha) => {
        const tiemposPanel = document.querySelector('#comp-tiempos');
        const buttons = tiemposPanel.querySelectorAll('[data-dispositivo-id]');

        buttons.forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const dispositivoId = button.dataset.dispositivoId;
                const hora = button.dataset.hora;

                try {
                    const endpoint = document.querySelector('meta[name="horario-seleccion-endpoint"]')?.content || '/administracion/horario/seleccion';
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            dispositivo_id: parseInt(dispositivoId),
                            hora: parseInt(hora),
                            fecha: fecha,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error(`Error: ${response.status}`);
                    }

                    const data = await response.json();
                    updateSelectionPanel(data.items);
                } catch (error) {
                    console.error('Error loading selection:', error);
                }
            });
        });
    };

    const updateSelectionPanel = (items) => {
        const seleccionList = document.querySelector('#sre-selection-list-items');
        if (!seleccionList) return;

        if (!items || items.length === 0) {
            seleccionList.innerHTML = `
                <div class="sre-empty-state">
                    <strong>Sin elementos seleccionados</strong>
                    <span>Selecciona una franja del horario para ver sus registros.</span>
                </div>
            `;
            return;
        }

        let html = '';
        for (let item of items) {
            const tipoClase = item.tipo.includes('association') ? 'association' : 'reservation';
            const fecha = new Date(item.inicio);
            const formattedDate = fecha.toLocaleDateString('es-CO', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            }) + ' - ' + fecha.toLocaleTimeString('es-CO', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true,
            });

            html += `
                <button
                    type="button"
                    class="sre-selection-item sre-selection-item--${tipoClase}"
                    onclick="document.querySelector('input[name=jugador_id]').value = '${item.usuario_id}'; document.querySelector('form[data-search-form]').submit();"
                    style="cursor: pointer; text-align: left; border: none; background: none; width: 100%; padding: inherit;"
                >
                    <h5 class="sre-selection-name">${item.nombre}</h5>
                    <div class="sre-selection-meta">
                        <span>${formattedDate}</span>
                        <span>${item.duracion_min} min</span>
                        <span>${item.tipo.includes('association') ? 'Asociación' : 'Reserva'}</span>
                    </div>
                </button>
            `;
        }
        seleccionList.innerHTML = html;
    };

    // Listen for changes in the date input
    const fechaInput = document.querySelector('.sre-fecha-horario-input');
    if (fechaInput) {
        fechaInput.addEventListener('change', updateSchedule);
    }

    // Initial update and set up periodic refresh
    updateSchedule();
    scheduleUpdateTimer = window.setInterval(updateSchedule, 60000); // Update every minute
});
