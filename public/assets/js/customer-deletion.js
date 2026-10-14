(() => {
    'use strict';
    const dialog = document.getElementById('customer-delete-dialog');
    if (!dialog) return;
    const form = document.getElementById('customer-delete-form');
    const password = document.getElementById('customer-delete-password');
    const summary = document.getElementById('customer-delete-summary');
    const error = document.getElementById('customer-delete-error');
    const submit = document.getElementById('customer-delete-submit');
    const cancel = document.getElementById('customer-delete-cancel');
    let deleteUrl = null;
    let csrf = null;
    let previewRequest = null;
    let deleting = false;
    let trigger = null;

    function showError(message) {
        error.textContent = message;
        error.classList.remove('hidden');
    }

    async function readResponse(response, isCurrent = () => true) {
        if (response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) {
            throw new Error('No se pudo confirmar la operación. Cerrá y volvé a abrir la confirmación; si el problema continúa, revisá tu sesión.');
        }
        const data = await response.json();
        if (data.csrf && isCurrent()) csrf = data.csrf;
        if (!response.ok) throw new Error(data.message || 'No se pudo completar la operación.');
        return data;
    }

    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-delete-customer]');
        if (!button || deleting) return;
        event.preventDefault();
        if (previewRequest) previewRequest.abort();
        const request = new AbortController();
        previewRequest = request;
        trigger = button;
        deleteUrl = button.dataset.deleteUrl;
        csrf = null;
        form.reset();
        submit.disabled = true;
        error.classList.add('hidden');
        summary.textContent = 'Consultando registros asociados…';
        if (!dialog.open) dialog.showModal();
        try {
            const response = await fetch(button.dataset.previewUrl, {
                credentials: 'same-origin', cache: 'no-store', signal: request.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (previewRequest !== request || !dialog.open) return;
            const data = await readResponse(response, () => previewRequest === request && dialog.open);
            if (previewRequest !== request || !dialog.open) return;
            const s = data.summary;
            summary.textContent = s.customer_name + ': ' + s.applications + ' solicitudes, ' + s.loans + ' préstamos, ' + s.installments + ' cuotas, ' + s.payments + ' pagos y ' + s.notifications + ' notificaciones. Incluye registros previamente eliminados de forma lógica.';
            submit.disabled = false;
            password.focus();
        } catch (exception) {
            if (exception.name !== 'AbortError' && previewRequest === request && dialog.open) {
                summary.textContent = 'No se pudieron consultar los registros asociados.';
                showError(exception.message);
            }
        }
    });

    cancel.addEventListener('click', () => { if (!deleting) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (deleting) event.preventDefault(); });
    dialog.addEventListener('close', () => {
        if (previewRequest) previewRequest.abort();
        previewRequest = null;
        password.value = '';
        trigger?.focus();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (deleting || submit.disabled || !deleteUrl || !csrf || !form.reportValidity()) return;
        deleting = true;
        submit.disabled = true;
        cancel.disabled = true;
        submit.textContent = 'Eliminando…';
        error.classList.add('hidden');
        try {
            // Method spoofing preserves CodeIgniter's form parsing and its DELETE route.
            const body = new URLSearchParams({ _method: 'DELETE', admin_password: password.value });
            const response = await fetch(deleteUrl, {
                method: 'POST', credentials: 'same-origin', cache: 'no-store', body,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', [csrf.header]: csrf.hash }
            });
            const data = await readResponse(response);
            password.value = '';
            deleting = false;
            dialog.close();
            if (document.getElementById('customers-list')) {
                document.dispatchEvent(new CustomEvent('customer:deleted', { detail: data }));
            } else {
                try { sessionStorage.setItem('customers-deletion-message', data.message); } catch (_) { /* Storage may be disabled. */ }
                window.location.assign(dialog.dataset.returnUrl);
            }
        } catch (exception) {
            showError(exception.message || 'No se pudo completar la eliminación.');
        } finally {
            password.value = '';
            deleting = false;
            submit.disabled = false;
            cancel.disabled = false;
            submit.textContent = 'Eliminar definitivamente';
        }
    });
})();
