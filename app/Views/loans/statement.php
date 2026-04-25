<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Estado de cuenta</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?= esc($statement['customer']['full_name']) ?> - <?= esc($statement['loan']['guid']) ?></p>
        </div>
        <a href="/prestamos/<?= esc($statement['loan']['guid']) ?>" class="text-sm text-slate-500 hover:text-slate-800 dark:hover:text-white">Volver al prestamo</a>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Cuotas totales</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($statement['summary']['total_installments']) ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Cuotas pagadas</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($statement['summary']['paid_installments']) ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total abonado</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(money($statement['summary']['total_paid'], $statement['loan']['currency'])) ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total pendiente</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(money($statement['summary']['total_pending'], $statement['loan']['currency'])) ?></p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
        <section class="glass-card overflow-hidden p-2">
            <div class="border-b border-slate-200 px-4 py-4 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Cuotas del cliente</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ordenadas y agrupadas por prestamo.</p>
            </div>

            <div class="space-y-4 p-4">
                <?php foreach ($statement['installments_grouped'] as $group): ?>
                    <section class="rounded-2xl border border-slate-200 dark:border-slate-700">
                        <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 dark:border-slate-700 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900 dark:text-white"><?= esc($group['loan']['guid'] ?? 'Prestamo') ?></h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    <?= esc(money($group['loan']['principal_amount'] ?? 0, $group['loan']['currency'] ?? 'ARS')) ?> - <?= esc(status_label($group['loan']['status'] ?? 'active')) ?>
                                </p>
                            </div>
                            <?php if (! empty($group['loan']['guid'])): ?>
                                <a href="/prestamos/<?= esc($group['loan']['guid']) ?>" class="text-sm text-sky-600 hover:text-sky-500 dark:text-sky-300">Ver prestamo</a>
                            <?php endif; ?>
                        </div>

                        <div class="hidden responsive-table lg:block">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-900/60">
                                    <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                                        <th class="px-4 py-4">Cuota</th>
                                        <th class="px-4 py-4">Vence</th>
                                        <th class="px-4 py-4">Total</th>
                                        <th class="px-4 py-4">Pagado</th>
                                        <th class="px-4 py-4">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <tr class="text-sm">
                                            <td class="px-4 py-4"><?= esc($item['installment_number']) ?></td>
                                            <td class="px-4 py-4"><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></td>
                                            <td class="px-4 py-4"><?= esc(money($item['total_amount'], $item['currency'])) ?></td>
                                            <td class="px-4 py-4"><?= esc(money($item['paid_amount'], $item['currency'])) ?></td>
                                            <td class="px-4 py-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($item['status'])) ?>"><?= esc(status_label($item['status'])) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid gap-3 p-4 lg:hidden">
                            <?php foreach ($group['items'] as $item): ?>
                                <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Cuota <?= esc($item['installment_number']) ?></p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></p>
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
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="glass-card overflow-hidden p-2">
            <div class="border-b border-slate-200 px-4 py-4 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Historial de pagos</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Agrupado por prestamo.</p>
            </div>

            <div class="space-y-4 p-4">
                <?php foreach ($statement['payments_grouped'] as $group): ?>
                    <section class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900 dark:text-white"><?= esc($group['loan']['guid'] ?? 'Prestamo') ?></h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= esc(money($group['loan']['principal_amount'] ?? 0, $group['loan']['currency'] ?? 'ARS')) ?></p>
                            </div>
                            <?php if (! empty($group['loan']['guid'])): ?>
                                <a href="/prestamos/<?= esc($group['loan']['guid']) ?>" class="text-sm text-sky-600 hover:text-sky-500 dark:text-sky-300">Ver</a>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4 space-y-3">
                            <?php foreach ($group['items'] as $payment): ?>
                                <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= esc(money($payment['amount'], $payment['currency'])) ?></p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= esc($payment['payment_method']) ?> - <?= esc(date('d/m/Y H:i', strtotime($payment['created_at']))) ?></p>
                                        </div>
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300"><?= esc($payment['reference_number']) ?></span>
                                    </div>
                                    <?php if (! empty($payment['notes'])): ?>
                                        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400"><?= esc($payment['notes']) ?></p>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>

                <?php if ($statement['payments_grouped'] === []): ?>
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        Todavia no hay pagos registrados para este cliente.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
