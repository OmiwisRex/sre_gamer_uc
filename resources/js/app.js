import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

import '../css/app.css';

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

// para los modales

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('dialog[data-validation-errors]').forEach((dialog) => {
        if (dialog instanceof HTMLDialogElement && !dialog.open) {
            dialog.showModal();
        }
    });

    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById(btn.dataset.modalOpen);
            if (modal) modal.showModal();
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('dialog')?.close();
        });
    });

    const searchForm = document.querySelector('[data-user-search-form]');
    if (!(searchForm instanceof HTMLFormElement)) {
        return;
    }

    const resultsPanel = searchForm.closest('.sre-resource-sidebar')?.querySelector('.sre-panel-content');
    const results = document.querySelector('[data-user-results]');
    const resultsStatus = document.querySelector('[data-user-results-status]');
    const detail = document.querySelector('[data-user-detail]');
    const detailTitle = document.querySelector('[data-user-detail-title]');
    const detailSubtitle = document.querySelector('[data-user-detail-subtitle]');

    if (
        !(resultsPanel instanceof HTMLElement)
        || !(results instanceof HTMLElement)
        || !(resultsStatus instanceof HTMLElement)
        || !(detail instanceof HTMLElement)
        || !(detailTitle instanceof HTMLElement)
        || !(detailSubtitle instanceof HTMLElement)
    ) {
        return;
    }

    let nextCursor = null;
    let hasMore = true;
    let isLoading = false;
    let searchSequence = 0;
    let searchController = null;
    let detailController = null;

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

    const setResultsStatus = (message) => {
        resultsStatus.className = 'sre-hint';
        resultsStatus.textContent = message;
    };

    const clearDetail = () => {
        detailTitle.textContent = 'Detalle del usuario';
        detailSubtitle.textContent = 'Selecciona un usuario de los resultados.';
        detail.replaceChildren(createEmptyState(
            'Selecciona un usuario',
            'Su información completa se mostrará en este espacio.',
        ));
    };

    const renderDetail = (usuario) => {
        detailTitle.textContent = `${usuario.nombres} ${usuario.apellidos}`;
        detailSubtitle.textContent = 'Información del registro seleccionado.';

        const fields = [
            ['Cédula', usuario.cedula],
            ['Código', usuario.codigo],
            ['Edad', usuario.edad],
            ['Correo electrónico', usuario.email],
            ['Género', usuario.genero],
            ['Estado', usuario.estado],
            ['Rol', usuario.rol],
        ];
        const list = document.createElement('dl');
        list.className = 'sre-detail-grid';

        fields.forEach(([label, value]) => {
            const item = document.createElement('div');
            item.className = 'sre-detail-item';

            const term = document.createElement('dt');
            term.className = 'sre-detail-label';
            term.textContent = label;

            const description = document.createElement('dd');
            description.className = 'sre-detail-value';
            description.textContent = value === null || value === '' ? 'No registrado' : String(value);

            item.append(term, description);
            list.append(item);
        });

        detail.replaceChildren(list);
    };

    const renderUserRecord = (usuario) => {
        const record = document.createElement('article');
        record.className = 'sre-record';
        record.setAttribute('role', 'listitem');
        record.dataset.userId = String(usuario.id);

        const button = document.createElement('button');
        button.className = 'sre-record-select';
        button.type = 'button';
        button.dataset.userId = String(usuario.id);
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-label', `Ver detalle de ${usuario.nombres} ${usuario.apellidos}`);

        const top = document.createElement('div');
        top.className = 'sre-record-top';
        const name = document.createElement('h4');
        name.className = 'sre-record-name';
        name.textContent = `${usuario.nombres} ${usuario.apellidos}`;
        const state = document.createElement('span');
        state.className = 'sre-badge';
        state.textContent = usuario.estado || 'Sin estado';
        top.append(name, state);

        const meta = document.createElement('div');
        meta.className = 'sre-record-meta';
        const identification = document.createElement('span');
        identification.textContent = `C.C. ${usuario.cedula}`;
        const role = document.createElement('span');
        role.textContent = usuario.rol || 'Sin rol';
        meta.append(identification, role);

        button.append(top, meta);
        record.append(button);

        return record;
    };

    const loadResults = async (reset = false) => {
        if (reset) {
            searchSequence += 1;
            searchController?.abort();
            isLoading = false;
            nextCursor = null;
            hasMore = true;
            results.replaceChildren();
            clearDetail();
        }

        if (isLoading || !hasMore) {
            return;
        }

        isLoading = true;
        const currentSequence = searchSequence;
        searchController = new AbortController();
        const searchUrl = new URL(searchForm.dataset.searchUrl, window.location.origin);
        new FormData(searchForm).forEach((value, key) => {
            if (typeof value === 'string' && value.trim() !== '') {
                searchUrl.searchParams.set(key, value.trim());
            }
        });
        if (nextCursor) {
            searchUrl.searchParams.set('cursor', nextCursor);
        }

        setResultsStatus(reset ? 'Buscando usuarios...' : 'Cargando más usuarios...');

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
                throw new Error(validationMessage || payload.message || 'No se pudieron cargar los usuarios.');
            }

            if (currentSequence !== searchSequence) {
                return;
            }

            if (reset && payload.data.length === 0) {
                results.append(createEmptyState(
                    'No hay usuarios para mostrar',
                    'Prueba con otros filtros de búsqueda.',
                ));
            } else {
                payload.data.forEach((usuario) => results.append(renderUserRecord(usuario)));
            }

            nextCursor = payload.next_cursor;
            hasMore = payload.has_more;
            setResultsStatus(hasMore
                ? 'Desplázate hasta el final para cargar más usuarios.'
                : 'Fin de los resultados.');

            if (hasMore && resultsPanel.clientHeight > 0
                && resultsPanel.scrollHeight <= resultsPanel.clientHeight + 1) {
                requestAnimationFrame(() => loadResults());
            }
        } catch (error) {
            if (error.name !== 'AbortError' && currentSequence === searchSequence) {
                console.error(error);
                setResultsStatus(error.message || 'Ocurrió un error al buscar usuarios.');
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
        if (!(event.target instanceof Element)) {
            return;
        }

        const button = event.target.closest('[data-user-id]');
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        results.querySelectorAll('.sre-record').forEach((record) => {
            record.removeAttribute('aria-current');
            record.querySelector('button')?.setAttribute('aria-pressed', 'false');
        });
        button.closest('.sre-record')?.setAttribute('aria-current', 'true');
        button.setAttribute('aria-pressed', 'true');
        detailTitle.textContent = button.querySelector('.sre-record-name')?.textContent || 'Detalle del usuario';
        detailSubtitle.textContent = 'Cargando información del registro seleccionado.';

        detailController?.abort();
        detailController = new AbortController();
        detail.replaceChildren(createEmptyState('Cargando detalle...', ''));

        try {
            const detailUrl = searchForm.dataset.detailUrl.replace(
                '__USER_ID__',
                encodeURIComponent(button.dataset.userId),
            );
            const response = await fetch(detailUrl, {
                headers: { Accept: 'application/json' },
                signal: detailController.signal,
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'No se pudo cargar el detalle del usuario.');
            }

            renderDetail(payload.data);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
                detail.replaceChildren(createEmptyState(
                    'No se pudo cargar el detalle',
                    error.message || 'Intenta seleccionar el usuario nuevamente.',
                ));
            }
        }
    });
});
