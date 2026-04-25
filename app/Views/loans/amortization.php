<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Cronograma de amortizacion</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?= esc($loan['customer_name']) ?> - <?= esc(money($loan['principal_amount'], $loan['currency'])) ?></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/prestamos/<?= esc($loan['guid']) ?>/estado-cuenta" class="icon-action <?= icon_button_classes('sky') ?>" title="Estado de cuenta" aria-label="Estado de cuenta">
                <?= app_icon('statement') ?>
            </a>
            <a href="/prestamos/<?= esc($loan['guid']) ?>" class="text-sm text-slate-500 hover:text-slate-800 dark:hover:text-white">Volver al prestamo</a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Cuotas del cliente</p>
            <p class="mt-3 text-3xl font-semibold"><?= count($installments) ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Pagadas</p>
            <p class="mt-3 text-3xl font-semibold"><?= count(array_filter($installments, static fn(array $item): bool => $item['status'] === 'paid')) ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Pendientes / Parciales</p>
            <p class="mt-3 text-3xl font-semibold"><?= count(array_filter($installments, static fn(array $item): bool => in_array($item['status'], ['pending', 'partial'], true))) ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">En mora</p>
            <p class="mt-3 text-3xl font-semibold"><?= count(array_filter($installments, static fn(array $item): bool => $item['status'] === 'overdue')) ?></p>
        </div>
    </div>

    <div class="glass-card p-5">
        <form method="get" class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-medium text-slate-900 dark:text-white">Filtrar estado</p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ([
                        'all' => 'Todas',
                        'paid' => 'Pagadas',
                        'pending' => 'Pendientes',
                        'partial' => 'Parciales',
                        'overdue' => 'En mora',
                    ] as $value => $label): ?>
                        <button type="submit" name="estado" value="<?= esc($value) ?>" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm transition <?= $statusFilter === $value ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800' ?>">
                            <?= app_icon('filter', 'h-4 w-4') ?>
                            <span><?= esc($label) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">Se muestran todas las cuotas del cliente vinculadas a sus prestamos.</p>
        </form>
    </div>

    <div class="glass-card overflow-hidden p-2">
        <?php if ($installments !== []): ?>
            <div class="hidden responsive-table lg:block">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900/60">
                        <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                            <th class="px-4 py-4">Prestamo</th>
                            <th class="px-4 py-4">Cuota</th>
                            <th class="px-4 py-4">Capital</th>
                            <th class="px-4 py-4">Interes</th>
                            <th class="px-4 py-4">Total</th>
                            <th class="px-4 py-4">Pagado</th>
                            <th class="px-4 py-4">Saldo</th>
                            <th class="px-4 py-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <?php foreach ($installments as $item): ?>
                            <tr class="text-sm">
                                <td class="px-4 py-4">
                                    <a href="/prestamos/<?= esc($item['loan_guid']) ?>" class="font-medium text-slate-900 hover:text-sky-600 dark:text-white dark:hover:text-sky-300"><?= esc($item['loan_label']) ?></a>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-medium"><?= esc($item['installment_number']) ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></p>
                                </td>
                                <td class="px-4 py-4"><?= esc(money($item['principal_amount'], $item['currency'])) ?></td>
                                <td class="px-4 py-4"><?= esc(money($item['interest_amount'], $item['currency'])) ?></td>
                                <td class="px-4 py-4"><?= esc(money($item['total_amount'], $item['currency'])) ?></td>
                                <td class="px-4 py-4"><?= esc(money($item['paid_amount'], $item['currency'])) ?></td>
                                <td class="px-4 py-4"><?= esc(money($item['remaining_balance'], $item['currency'])) ?></td>
                                <td class="px-4 py-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($item['status'])) ?>"><?= esc(status_label($item['status'])) ?></span></td>
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
                                <a href="/prestamos/<?= esc($item['loan_guid']) ?>" class="text-sm font-semibold text-slate-900 dark:text-white"><?= esc($item['loan_label']) ?></a>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cuota <?= esc($item['installment_number']) ?> - <?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></p>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($item['status'])) ?>"><?= esc(status_label($item['status'])) ?></span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Capital</p>
                                <p class="font-medium"><?= esc(money($item['principal_amount'], $item['currency'])) ?></p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Interes</p>
                                <p class="font-medium"><?= esc(money($item['interest_amount'], $item['currency'])) ?></p>
                            </div>
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
        <?php else: ?>
            <div class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                No hay cuotas para el filtro seleccionado.
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
