<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Cuotas pendientes</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Proxima cuota pendiente del mes en curso por prestamo.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Proximas cuotas del mes</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($summary['total']) ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Saldo pendiente</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(money($summary['amount_due'])) ?></p>
        </div>
    </div>

    <div class="glass-card overflow-hidden p-2">
        <?php if ($installments !== []): ?>
            <div class="hidden responsive-table lg:block">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900/60">
                        <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                            <th class="px-4 py-4">Cliente</th>
                            <th class="px-4 py-4">Prestamo</th>
                            <th class="px-4 py-4">Cuota</th>
                            <th class="px-4 py-4">Vencimiento</th>
                            <th class="px-4 py-4">Total</th>
                            <th class="px-4 py-4">Pagado</th>
                            <th class="px-4 py-4">Pendiente</th>
                            <th class="px-4 py-4">Estado</th>
                            <th class="px-4 py-4 text-right">Accion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <?php foreach ($installments as $item): ?>
                            <tr class="text-sm">
                                <td class="px-4 py-4 font-medium text-slate-900 dark:text-white"><?= esc($item['customer_name']) ?></td>
                                <td class="px-4 py-4">
                                    <a href="/prestamos/<?= esc($item['loan_guid']) ?>" class="font-medium text-slate-900 hover:text-sky-600 dark:text-white dark:hover:text-sky-300"><?= esc($item['loan_label']) ?></a>
                                </td>
                                <td class="px-4 py-4"><?= esc($item['installment_number']) ?></td>
                                <td class="px-4 py-4"><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></td>
                                <td class="px-4 py-4"><?= esc(money($item['total_amount'], $item['currency'])) ?></td>
                                <td class="px-4 py-4"><?= esc(money($item['paid_amount'], $item['currency'])) ?></td>
                                <td class="px-4 py-4 font-medium"><?= esc(money($item['amount_due'], $item['currency'])) ?></td>
                                <td class="px-4 py-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($item['status'])) ?>"><?= esc(status_label($item['status'])) ?></span></td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end">
                                        <a href="/pagos/crear/<?= esc($item['guid']) ?>?return=/cuotas-pendientes" class="icon-action <?= icon_button_classes('accent') ?>" title="Cobrar cuota" aria-label="Cobrar cuota">
                                            <?= app_icon('cash') ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid gap-3 lg:hidden">
                <?php foreach ($installments as $item): ?>
                    <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= esc($item['customer_name']) ?></p>
                                <a href="/prestamos/<?= esc($item['loan_guid']) ?>" class="mt-1 block text-sm text-sky-600 dark:text-sky-300"><?= esc($item['loan_label']) ?></a>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cuota <?= esc($item['installment_number']) ?> - <?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></p>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($item['status'])) ?>"><?= esc(status_label($item['status'])) ?></span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Total</p>
                                <p class="font-medium"><?= esc(money($item['total_amount'], $item['currency'])) ?></p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Pagado</p>
                                <p class="font-medium"><?= esc(money($item['paid_amount'], $item['currency'])) ?></p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-slate-500 dark:text-slate-400">Pendiente</p>
                                <p class="font-medium"><?= esc(money($item['amount_due'], $item['currency'])) ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="/pagos/crear/<?= esc($item['guid']) ?>?return=/cuotas-pendientes" class="inline-flex items-center gap-2 rounded-2xl border <?= icon_button_classes('accent') ?> px-4 py-3 text-sm font-medium">
                                <?= app_icon('cash', 'h-4 w-4') ?>
                                <span>Cobrar cuota</span>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                No hay cuotas pendientes para el mes en curso.
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
