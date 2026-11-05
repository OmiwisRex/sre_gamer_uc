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

});
