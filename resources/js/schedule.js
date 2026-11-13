import { formatDateTimeWithPeriod } from './dateFormatter.js';

document.addEventListener('DOMContentLoaded', () => {
    const panel = document.querySelector('#comp-tiempos');
    const selectionList = document.querySelector('#sre-selection-list-items');
    const dateInput = panel?.querySelector('.sre-fecha-horario-input');
    const errorMessage = panel?.querySelector('.sre-timeline-error');
    if (!(panel instanceof HTMLElement) || !(selectionList instanceof HTMLElement)
        || !(dateInput instanceof HTMLInputElement) || !(errorMessage instanceof HTMLElement)) {
        return;
    }

    let scheduleController;
    let scheduleDate;
    let selectionController;
    let selected = null;
    let renderedDate = dateInput.value;

    const showError = (message) => {
        errorMessage.textContent = message;
        errorMessage.hidden = !message;
    };

    const SCROLL_POSITION_KEY = 'sre-timeline-scroll-position';

    const saveScrollPosition = () => {
        const scrollContainer = panel.querySelector('.sre-timeline-scroll');
        if (scrollContainer instanceof HTMLElement) {
            localStorage.setItem(SCROLL_POSITION_KEY, scrollContainer.scrollLeft);
        }
    };

    const restoreScrollPosition = () => {
        const scrollContainer = panel.querySelector('.sre-timeline-scroll');
        if (scrollContainer instanceof HTMLElement) {
            const savedPosition = localStorage.getItem(SCROLL_POSITION_KEY);
            if (savedPosition !== null) {
                const scrollValue = parseInt(savedPosition, 10);
                scrollContainer.scrollLeft = scrollValue;
            }
        }
    };

    const clearSelection = () => {
        selectionController?.abort();
        selected = null;
        const empty = document.createElement('div');
        empty.className = 'sre-empty-state';
        const title = document.createElement('strong');
        title.textContent = 'Sin elementos seleccionados';
        const description = document.createElement('span');
        description.textContent = 'Selecciona una franja del horario para ver sus registros.';
        empty.append(title, description);
        selectionList.replaceChildren(empty);
    };

    const loadSelection = async () => {
        if (!selected) return;
        selectionController?.abort();
        const controller = new AbortController();
        selectionController = controller;
        try {
            const response = await fetch(panel.dataset.selectionEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': panel.dataset.csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify(selected),
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(`No se pudo cargar la selección (${response.status}).`);
            const { items } = await response.json();
            if (controller.signal.aborted) return;
            selectionList.replaceChildren();
            if (items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'sre-empty-state';
                empty.textContent = 'No hay registros para esta franja.';
                selectionList.append(empty);
                showError('');
                return;
            }
            for (const item of items) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `sre-selection-item sre-selection-item--${item.tipo}`;
                const name = document.createElement('h5');
                name.className = 'sre-selection-name';
                name.textContent = item.nombre;
                const meta = document.createElement('div');
                meta.className = 'sre-selection-meta';
                for (const text of [formatDateTimeWithPeriod(item.inicio), `${item.duracion_min} min`, item.recurso, item.evento]) {
                    if (!text) continue;
                    const span = document.createElement('span');
                    span.textContent = text;
                    meta.append(span);
                }
                button.append(name, meta);
                button.addEventListener('click', () => {
                    const form = document.querySelector('form[data-search-form]');
                    const input = form?.querySelector('input[name="jugador_id"]');
                    if (!(form instanceof HTMLFormElement) || !(input instanceof HTMLInputElement)) {
                        showError('No se encontró el formulario de búsqueda del jugador.');
                        return;
                    }
                    input.value = item.identificacion;
                    form.requestSubmit();
                });
                selectionList.append(button);
            }
            showError('');
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
                showError('No se pudo cargar la selección. Intenta nuevamente.');
            }
        }
    };

    const updateSchedule = async () => {
        const date = dateInput.value;
        if (scheduleController && scheduleDate === date) return;
        scheduleController?.abort();
        if (!date) {
            showError('Selecciona una fecha para consultar el horario.');
            return;
        }
        const controller = new AbortController();
        scheduleController = controller;
        scheduleDate = date;
        try {
            const url = new URL(dateInput.dataset.endpoint, window.location.href);
            url.searchParams.set('fecha', date);
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(`No se pudo actualizar el horario (${response.status}).`);
            const data = await response.json();
            if (controller.signal.aborted || date !== dateInput.value) return;
            const grid = panel.querySelector('.sre-timeline-grid');
            const updatedGrid = new DOMParser().parseFromString(data.html, 'text/html').querySelector('.sre-timeline-grid');
            if (!(grid instanceof HTMLElement) || !(updatedGrid instanceof HTMLElement)) {
                throw new Error('La respuesta no contiene el horario.');
            }
            grid.replaceWith(updatedGrid);
            
            restoreScrollPosition();
            
            renderedDate = date;
            showError('');
            if (selected) {
                const selector = selected.tipo_dispo_id
                    ? `[data-tipo-dispo-id="${selected.tipo_dispo_id}"][data-franja="${selected.franja}"]`
                    : `[data-dispositivo-id="${selected.dispositivo_id}"][data-franja="${selected.franja}"]`;
                if (updatedGrid.querySelector(selector)) {
                    loadSelection();
                } else {
                    clearSelection();
                }
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
                showError('No se pudo actualizar el horario. Intenta nuevamente.');
            }
        } finally {
            if (scheduleController === controller) {
                scheduleController = null;
            }
        }
    };

    panel.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-franja]');
        if (!(button instanceof HTMLButtonElement)) return;
        if (renderedDate !== dateInput.value) {
            showError('El horario de la fecha seleccionada aún no está disponible.');
            return;
        }
        selected = {
            fecha: dateInput.value,
            franja: Number(button.dataset.franja),
            ...(button.dataset.tipoDispoId
                ? { tipo_dispo_id: Number(button.dataset.tipoDispoId) }
                : { dispositivo_id: Number(button.dataset.dispositivoId) }),
        };
        loadSelection();
    });
    const scrollContainer = panel.querySelector('.sre-timeline-scroll');
    if (scrollContainer instanceof HTMLElement) {
        scrollContainer.addEventListener('scroll', saveScrollPosition);
    }
    dateInput.addEventListener('change', () => {
        clearSelection();
        updateSchedule();
    });
    document.addEventListener('horario-actualizar', updateSchedule);
    updateSchedule();
    restoreScrollPosition();
    window.setInterval(updateSchedule, 60000);
});
