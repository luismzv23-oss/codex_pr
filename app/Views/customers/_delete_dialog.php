<?php if (auth()->user()?->can('customers.delete')): ?>
<dialog id="customer-delete-dialog" aria-labelledby="customer-delete-title" aria-describedby="customer-delete-warning" class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-black/50 dark:border-slate-700 dark:bg-slate-900 dark:text-white" data-return-url="<?= esc(site_url('clientes'), 'attr') ?>">
    <h2 id="customer-delete-title" class="text-xl font-semibold">Eliminar cliente definitivamente</h2>
    <p id="customer-delete-warning" class="mt-3 text-sm">Se eliminarán el cliente, sus solicitudes, préstamos, cuotas, pagos, notificaciones y contratos. Esta acción no se puede deshacer.</p>
    <p id="customer-delete-summary" class="mt-4 rounded-xl bg-slate-100 p-4 text-sm dark:bg-slate-800" aria-live="polite">Consultando registros asociados…</p>
    <p id="customer-delete-error" class="mt-3 hidden text-sm text-red-600 dark:text-red-400" role="alert"></p>
    <form id="customer-delete-form" class="mt-4 space-y-4">
        <label class="block space-y-2">
            <span class="text-sm font-medium">Tu contraseña de administrador</span>
            <input id="customer-delete-password" name="admin_password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-800">
        </label>
        <div class="flex justify-end gap-3">
            <button id="customer-delete-cancel" type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm dark:border-slate-600">Cancelar</button>
            <button id="customer-delete-submit" type="submit" disabled class="rounded-xl bg-red-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Eliminar definitivamente</button>
        </div>
    </form>
</dialog>
<?php endif; ?>
