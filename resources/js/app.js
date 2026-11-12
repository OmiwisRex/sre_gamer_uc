import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

import '../css/app.css';
import './schedule.js';
import { formatDateTimeWithPeriod } from './dateFormatter.js';

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const openButton = event.target.closest('[data-modal-open]');
    if (openButton instanceof HTMLElement) {
        const dialogId = openButton.dataset.modalOpen;
        const dialog = dialogId ? document.getElementById(dialogId) : null;

        if (dialog instanceof HTMLDialogElement) {
            dialog.showModal();
        }

        return;
    }

    const closeButton = event.target.closest('[data-modal-close]');
    if (closeButton) {
        closeButton.closest('dialog')?.close();

        return;
    }

    if (event.target instanceof HTMLDialogElement && event.target.open) {
        event.target.close();
    }
});

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const submitButton = event.target.closest('[data-user-type]');
    if (!(submitButton instanceof HTMLButtonElement)) {
        return;
    }

    const form = submitButton.form;
    if (!form?.matches('[data-user-registration]')) {
        return;
    }

    form.querySelectorAll('[data-community-required]').forEach((field) => {
        field.required = submitButton.dataset.userType === 'community';
    });
});

document.addEventListener('submit', (event) => {
    if (event.target instanceof HTMLFormElement && event.target.matches('[data-ui-only]')) {
        event.preventDefault();
    }
});

const resourceSearchConfigurations = {
    usuario: {
        plural: 'usuarios',
        detailTitle: 'Detalle del usuario',
        emptyDetailTitle: 'Selecciona un usuario',
        emptyDetailDescription: 'Su información completa se mostrará en este espacio.',
        name: (record) => `${record.nombres} ${record.apellidos}`,
        meta: (record) => [`C.C. ${record.cedula}`, record.rol || 'Sin rol'],
        details: (record) => [
            ['Cédula', record.cedula],
            ['Código', record.codigo],
            ['Edad', record.edad],
            ['E-Mail', record.email],
            ['Género', record.genero],
            ['Estado', record.estado],
            ['Rol', record.rol],
        ],
    },
    dispositivo: {
        plural: 'dispositivos',
        detailTitle: 'Detalle del dispositivo',
        emptyDetailTitle: 'Selecciona un dispositivo',
        emptyDetailDescription: 'Su tipo, estado y descripción se mostrarán en este espacio.',
        name: (record) => record.nombre,
        meta: (record) => [record.tipo || 'Sin tipo'],
        details: (record) => [
            ['Tipo', record.tipo],
            ['Estado', record.estado],
            ['Dirección', record.direccion],
            ['D', record.descripcion]
        ],
    },
    evento: {
        plural: 'eventos',
        detailTitle: 'Detalle del evento',
        emptyDetailTitle: 'Selecciona un evento',
        emptyDetailDescription: 'Su descripción, programación y dispositivos se mostrarán en este espacio.',
        name: (record) => record.nombre,
        meta: (record) => [
            record.inicio ? `Inicio: ${formatInicio(record.inicio)}` : 'Fecha por definir',
            `Aforo: ${record.aforo}`,
        ],
        details: (record) => [
            ['Organiza', record.organizador],
            ['Inicio', formatInicio(record.inicio)],
            ['Duración', record.duracion],
            ['Aforo', record.aforo],
            ['Estado', record.estado],
            ['Admin', record.administracion],
            ['D', record.descripcion],
            ['Dispositivos vinculados', ' '],
            ...(record.dispositivos ?? []).map((name, index) => [
                `D${index + 1}`,
                name
            ]),
        ],
    },
    observacion: {
        plural: 'observaciones',
        detailTitle: 'Detalle de observación',
        emptyDetailTitle: 'Selecciona una observación',
        emptyDetailDescription: 'El mensaje completo y sus datos asociados se mostrarán aquí.',
        recordClass: 'sre-record sre-observation-record',
        previewClass: 'sre-observation-preview',
        name: (record) => record.fecha,
        state: (record) => record.relacion,
        meta: (record) => [record.mensaje],
        details: (record) => [
            ['Fecha y Hora', record.fecha],
            ['Administrador', record.administrador],
            ['Jugador Asociado', record.jugador],
            ['Dispositivo', record.dispositivo],
            ['Evento', record.evento],
            ['Mensaje', record.mensaje, 'sre-observation-full-message'],
        ],
    },
    auditoria: {
        plural: 'auditorías',
        renderRecord: (item) => {
            const row = document.createElement('tr');
            [
                ['Fecha y hora', item.fecha],
                ['Acción', item.accion],
                ['Tabla', item.tabla],
                ['Índice', item.indice],
                ['Usuario responsable', item.usuario],
            ].forEach(([label, value], index) => {
                const cell = document.createElement('td');
                cell.dataset.label = label;
                if (index === 2 || index === 4) {
                    const text = document.createElement('span');
                    text.className = index === 2 ? 'sre-audit-table-name' : 'sre-audit-user';
                    text.textContent = value;
                    cell.append(text);
                    if (index === 4 && item.cedula) {
                        const identification = document.createElement('span');
                        identification.className = 'sre-audit-user-id';
                        identification.textContent = `C.C. ${item.cedula}`;
                        cell.append(identification);
                    }
                } else {
                    cell.textContent = value;
                }
                row.append(cell);
            });

            return row;
        },
    },
};

const formatInicio = (fecha) => {
    return formatDateTimeWithPeriod(fecha);
};

const createEmptyState = (title, description) => {
    const emptyState = document.createElement('div');
    emptyState.className = 'sre-empty-state';

    const strong = document.createElement('strong');
    strong.textContent = title;
    const span = document.createElement('span');
    span.textContent = description;

    emptyState.append(strong, span);

    return emptyState;
};

const setupResourceSearch = (searchForm) => {
    const configuration = resourceSearchConfigurations[searchForm.dataset.resourceType];
    const page = searchForm.closest('.sre-page');
    const results = page?.querySelector('[data-resource-results]');
    const resultsPanel = results?.closest('[data-resource-scroll], .sre-panel-content');
    const resultsStatus = page?.querySelector('[data-resource-results-status]');
    const detail = page?.querySelector('[data-resource-detail]');
    const detailTitle = page?.querySelector('[data-resource-detail-title]');
    const detailAction = page?.querySelector('[data-resource-detail-action]');

    if (
        !configuration
        || !(resultsPanel instanceof HTMLElement)
        || !(results instanceof HTMLElement)
        || !(resultsStatus instanceof HTMLElement)
        || (configuration.details && (!(detail instanceof HTMLElement) || !(detailTitle instanceof HTMLElement)))
    ) {
        return;
    }

    let nextCursor = null;
    let hasMore = true;
    let isLoading = false;
    let searchSequence = 0;
    let searchController = null;
    let detailController = null;
    let activeFilters = new URLSearchParams();

    const setResultsStatus = (message) => {
        resultsStatus.className = 'sre-hint';
        resultsStatus.textContent = message;
    };

    const clearDetail = () => {
        if (!configuration.details) {
            return;
        }
        detailTitle.textContent = configuration.detailTitle;
        detailAction?.replaceChildren();
        detail.replaceChildren(createEmptyState(
            configuration.emptyDetailTitle,
            configuration.emptyDetailDescription,
        ));
    };

    const renderRecord = (item) => {
        if (configuration.renderRecord) {
            return configuration.renderRecord(item);
        }
        const nameText = configuration.name(item);
        const record = document.createElement('article');
        record.className = configuration.recordClass || 'sre-record';
        record.setAttribute('role', 'listitem');

        const button = document.createElement('button');
        button.className = 'sre-record-select';
        button.type = 'button';
        button.dataset.recordId = String(item.id);
        if (item.edit_url) {
            button.dataset.editUrl = item.edit_url;
        }
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-label', `Ver detalle de ${nameText}`);

        const top = document.createElement('div');
        top.className = 'sre-record-top';
        const name = document.createElement('h4');
        name.className = 'sre-record-name';
        name.textContent = nameText;
        const state = document.createElement('span');
        state.className = 'sre-badge';
        state.textContent = configuration.state
            ? configuration.state(item)
            : item.estado || 'Sin estado';
        top.append(name, state);

        const meta = document.createElement('div');
        meta.className = 'sre-record-meta';
        configuration.meta(item).forEach((value, index) => {
            const entry = document.createElement('span');
            entry.textContent = value;
            if (configuration.previewClass && index === 0) {
                entry.className = configuration.previewClass;
            }

            if (index === 0) {
                entry.classList.add('sre-record-type');
            }

            meta.append(entry);
        });

        button.append(top, meta);
        record.append(button);

        return record;
    };

    const renderDetail = (item) => {
        detailTitle.textContent = configuration.name(item);
        const list = document.createElement('dl');
        list.className = 'sre-detail-list';

        configuration.details(item).forEach(([label, value, valueClass]) => {
            const row = document.createElement('div');
            row.className = 'sre-detail-row';

            const term = document.createElement('dt');
            term.className = 'sre-detail-label';
            term.textContent = `${label}:`;

            const description = document.createElement('dd');
            description.className = 'sre-detail-value';
            if (valueClass) {
                description.classList.add(valueClass);
            }
            description.textContent = value === null || value === ''
                ? '???'
                : String(value);

            row.append(term, description);
            list.append(row);
        });

        detail.replaceChildren(list);
    };

    const loadResults = async (reset = false) => {
        if (reset) {
            searchSequence += 1;
            searchController?.abort();
            detailController?.abort();
            isLoading = false;
            nextCursor = null;
            hasMore = true;
            results.replaceChildren();
            clearDetail();
            activeFilters = new URLSearchParams();
            new FormData(searchForm).forEach((value, key) => {
                if (typeof value === 'string' && value.trim() !== '') {
                    activeFilters.set(key, value.trim());
                }
            });
        }

        if (isLoading || !hasMore) {
            return;
        }

        isLoading = true;
        const currentSequence = searchSequence;
        searchController = new AbortController();
        const searchUrl = new URL(searchForm.dataset.searchUrl, window.location.origin);
        searchUrl.search = activeFilters.toString();
        if (nextCursor) {
            searchUrl.searchParams.set('cursor', nextCursor);
        }

        setResultsStatus(reset
            ? `Buscando ${configuration.plural}...`
            : `Cargando más ${configuration.plural}...`);

        try {
            const response = await fetch(searchUrl, {
                headers: { Accept: 'application/json' },
                signal: searchController.signal,
            });
            const payload = await response.json();

            if (!response.ok) {
                const validationMessage = payload.errors
                    ? Object.values(payload.errors).flat()[0]
                    : null;
                throw new Error(validationMessage || payload.message || `No se pudieron cargar los ${configuration.plural}.`);
            }

            if (currentSequence !== searchSequence) {
                return;
            }

            if (reset && payload.data.length === 0) {
                setResultsStatus(`No hay ${configuration.plural} para mostrar. Prueba con otros filtros de búsqueda.`);
            } else {
                payload.data.forEach((item) => results.append(renderRecord(item)));
            }

            nextCursor = payload.next_cursor;
            hasMore = payload.has_more;
            if (payload.data.length > 0) {
                setResultsStatus(hasMore
                    ? `Desplázate hasta el final para cargar más ${configuration.plural}.`
                    : 'Fin de los resultados.');
            }

            if (hasMore && resultsPanel.clientHeight > 0
                && resultsPanel.scrollHeight <= resultsPanel.clientHeight + 1) {
                requestAnimationFrame(() => loadResults());
            }
        } catch (error) {
            if (error.name !== 'AbortError' && currentSequence === searchSequence) {
                console.error(error);
                setResultsStatus(error.message || `Ocurrió un error al buscar ${configuration.plural}.`);
            }
        } finally {
            if (currentSequence === searchSequence) {
                isLoading = false;
            }
        }
    };

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadResults(true);
    });

    resultsPanel.addEventListener('scroll', () => {
        if (resultsPanel.scrollHeight - resultsPanel.scrollTop - resultsPanel.clientHeight < 100) {
            loadResults();
        }
    });

    results.addEventListener('click', async (event) => {
        if (!configuration.details) {
            return;
        }
        if (!(event.target instanceof Element)) {
            return;
        }

        const button = event.target.closest('[data-record-id]');
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        results.querySelectorAll('.sre-record').forEach((record) => {
            record.removeAttribute('aria-current');
            record.querySelector('button')?.setAttribute('aria-pressed', 'false');
        });
        button.closest('.sre-record')?.setAttribute('aria-current', 'true');
        button.setAttribute('aria-pressed', 'true');
        detailTitle.textContent = button.querySelector('.sre-record-name')?.textContent || configuration.detailTitle;

        detailAction?.replaceChildren();
        const editUrl = button.dataset.editUrl;
        if (editUrl && detailAction) {
            const editLink = document.createElement('a');
            editLink.className = 'sre-button sre-button--secondary sre-button--small';
            editLink.href = editUrl;
            editLink.textContent = 'Editar';
            detailAction.replaceChildren(editLink);
        }

        detailController?.abort();
        detailController = new AbortController();
        detail.replaceChildren(createEmptyState('Cargando detalle...', ''));

        try {
            const detailUrl = searchForm.dataset.detailUrl.replace(
                '__RECORD_ID__',
                encodeURIComponent(button.dataset.recordId),
            );
            const response = await fetch(detailUrl, {
                headers: { Accept: 'application/json' },
                signal: detailController.signal,
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || `No se pudo cargar el detalle de ${configuration.plural}.`);
            }

            renderDetail(payload.data);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
                detail.replaceChildren(createEmptyState(
                    'No se pudo cargar el detalle',
                    error.message || 'Intenta seleccionar el registro nuevamente.',
                ));
            }
        }
    });

    if (searchForm.hasAttribute('data-load-initial')) {
        loadResults(true);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    let hoursRefreshInProgress = false;
    const refreshPlayerHours = async () => {
        const hoursPanel = document.querySelector('[data-hours-refresh-url]');
        if (!(hoursPanel instanceof HTMLElement)) {
            window.clearInterval(hoursRefreshTimer);

            return;
        }

        if (hoursRefreshInProgress) {
            return;
        }

        hoursRefreshInProgress = true;
        try {
            const response = await fetch(hoursPanel.dataset.hoursRefreshUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': hoursPanel.dataset.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
            });
            if (!response.ok) {
                throw new Error(`No se pudieron actualizar las horas (${response.status}).`);
            }

            const html = await response.text();
            const refreshedPanel = new DOMParser()
                .parseFromString(html, 'text/html')
                .querySelector('#comp-horas');
            if (!(refreshedPanel instanceof HTMLElement)) {
                throw new Error('La respuesta de actualización no contiene el componente de horas.');
            }

            hoursPanel.replaceWith(refreshedPanel);
        } catch (error) {
            console.error(error);
        } finally {
            hoursRefreshInProgress = false;
        }
    };
    const initialHoursPanel = document.querySelector('[data-hours-refresh-url]');
    const hoursRefreshTimer = initialHoursPanel instanceof HTMLElement
        ? window.setInterval(refreshPlayerHours, Number(initialHoursPanel.dataset.refreshInterval) || 5000)
        : null;

    document.querySelectorAll('dialog[data-validation-errors]').forEach((dialog) => {
        if (dialog instanceof HTMLDialogElement && !dialog.open) {
            dialog.showModal();
        }
    });

    document.querySelectorAll('[data-modal-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById(btn.dataset.modalOpen);
            if (modal instanceof HTMLDialogElement) {
                modal.showModal();
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => {
            btn.closest('dialog')?.close();
        });
    });

    document.querySelectorAll('[data-user-edit-role]').forEach((roleSelect) => {
        if (!(roleSelect instanceof HTMLSelectElement)) {
            return;
        }

        const form = roleSelect.form;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const updateCommunityRequirements = () => {
            const isCommunity = roleSelect.value !== '';
            roleSelect.required = isCommunity;
            form.querySelectorAll('[data-user-edit-community]').forEach((field) => {
                field.required = isCommunity;
            });
        };

        roleSelect.addEventListener('change', updateCommunityRequirements);
        updateCommunityRequirements();
    });

    document.querySelectorAll('[data-resource-search-form]').forEach((searchForm) => {
        if (searchForm instanceof HTMLFormElement) {
            setupResourceSearch(searchForm);
        }
    });

    document.querySelectorAll('[data-observation-form]').forEach((form) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const message = form.elements.namedItem('mensaje');
        if (!(message instanceof HTMLTextAreaElement)) {
            return;
        }

        form.addEventListener('submit', (event) => {
            if (message.value.trim() === '') {
                event.preventDefault();
                message.setCustomValidity('El mensaje de la observación es obligatorio.');
                message.reportValidity();
            } else {
                message.setCustomValidity('');
            }
        });

        message.addEventListener('input', () => message.setCustomValidity(''));
    });
});
