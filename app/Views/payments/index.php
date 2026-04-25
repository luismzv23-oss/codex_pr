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
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
