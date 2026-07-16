<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Pagos</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Registro base de cobranzas y referencias operativas.</p>
        </div>
        <a href="/pagos/crear" class="icon-action <?= icon_button_classes('accent') ?>" title="Registrar pago" aria-label="Registrar pago">
            <?= app_icon('cash') ?>
        </a>
    </div>
    <form method="get" action="/pagos" class="glass-card grid gap-4 p-5 sm:grid-cols-2 md:grid-cols-4 items-end">
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Buscar</span>
            <input type="text" name="buscar" value="<?= esc($buscar) ?>" placeholder="Cliente, referencia, préstamo..." class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Método de Pago</span>
            <select name="metodo" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="cash" <?= $metodo === 'cash' ? 'selected' : '' ?>>Efectivo</option>
                <option value="transfer" <?= $metodo === 'transfer' ? 'selected' : '' ?>>Transferencia</option>
                <option value="card" <?= $metodo === 'card' ? 'selected' : '' ?>>Tarjeta</option>
                <option value="check" <?= $metodo === 'check' ? 'selected' : '' ?>>Cheque</option>
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Moneda</span>
            <select name="moneda" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todas</option>
                <option value="ARS" <?= $moneda === 'ARS' ? 'selected' : '' ?>>ARS</option>
                <option value="USD" <?= $moneda === 'USD' ? 'selected' : '' ?>>USD</option>
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950 px-4 py-2.5 text-sm font-medium transition hover:bg-slate-800 dark:hover:bg-slate-100 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <?= app_icon('filter', 'h-4 w-4') ?>
                <span>Filtrar</span>
            </button>
            <?php if ($buscar !== '' || $metodo !== '' || $moneda !== ''): ?>
                <a href="/pagos" class="inline-flex items-center justify-center p-2.5 rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" title="Limpiar filtros">
                    <?= app_icon('close', 'h-5 w-5') ?>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-900/60">
                <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                    <th class="px-6 py-4">Cliente</th>
                    <th class="px-6 py-4">Monto</th>
                    <th class="px-6 py-4">Metodo</th>
                    <th class="px-6 py-4">Referencia</th>
                    <th class="px-6 py-4">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                <?php if ($payments === []): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            No se encontraron registros de pago para los filtros seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr class="text-sm">
                            <td class="px-6 py-4">
                                <p class="font-medium text-slate-900 dark:text-white"><?= esc($payment['customer_name']) ?></p>
                                <p class="text-slate-500 dark:text-slate-400"><?= esc($payment['loan_guid']) ?></p>
                            </td>
                            <td class="px-6 py-4"><?= esc(money($payment['amount'], $payment['currency'])) ?></td>
                            <td class="px-6 py-4"><?= esc(ucfirst($payment['payment_method'])) ?></td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400"><?= esc($payment['reference_number'] ?: '-') ?></td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400"><?= esc(date('d/m/Y H:i', strtotime($payment['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
