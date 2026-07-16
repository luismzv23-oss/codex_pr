<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Cuotas pendientes</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Proxima cuota pendiente del mes en curso por prestamo.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="glass-card p-5">
            <?php
            $periodoLabel = 'Cuotas del mes';
            if ($periodo === 'overdue') {
                $periodoLabel = 'Cuotas vencidas (Mora)';
            } elseif ($periodo === 'all') {
                $periodoLabel = 'Todas las cuotas pendientes';
            }
            ?>
            <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc($periodoLabel) ?></p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($summary['total']) ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Saldo pendiente</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(money($summary['amount_due'])) ?></p>
        </div>
    </div>

    <form method="get" action="/cuotas-pendientes" class="glass-card grid gap-4 p-5 sm:grid-cols-2 md:grid-cols-4 items-end">
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Buscar</span>
            <input type="text" name="buscar" value="<?= esc($buscar) ?>" placeholder="Cliente o préstamo..." class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vencimiento</span>
            <select name="periodo" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="current_month" <?= $periodo === 'current_month' ? 'selected' : '' ?>>Este mes</option>
                <option value="overdue" <?= $periodo === 'overdue' ? 'selected' : '' ?>>Todos los vencidos (Mora)</option>
                <option value="all" <?= $periodo === 'all' ? 'selected' : '' ?>>Todos los pendientes</option>
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</span>
            <select name="estado" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="pending" <?= $estado === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                <option value="partial" <?= $estado === 'partial' ? 'selected' : '' ?>>Parcial</option>
                <option value="overdue" <?= $estado === 'overdue' ? 'selected' : '' ?>>En mora</option>
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950 px-4 py-2.5 text-sm font-medium transition hover:bg-slate-800 dark:hover:bg-slate-100 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <?= app_icon('filter', 'h-4 w-4') ?>
                <span>Filtrar</span>
            </button>
            <?php if ($buscar !== '' || $estado !== '' || $periodo !== 'current_month'): ?>
                <a href="/cuotas-pendientes" class="inline-flex items-center justify-center p-2.5 rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" title="Limpiar filtros">
                    <?= app_icon('close', 'h-5 w-5') ?>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <div class="glass-card overflow-hidden p-2">
        <?php if ($installments !== []): ?>
            <div class="hidden responsive-table lg:block">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900/60">
                        <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                            <th class="px-4 py-4">Cliente</th>
                            <th class="px-4 py-4">Prestamo</th>
                            <th class="px-4 py-4">Cuota</th>
                            <th class="px-4 py-4">Generacion</th>
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
                                <td class="px-4 py-4"><?= esc(date('d/m/Y', strtotime($item['generation_date'] ?? date('Y-m-01', strtotime($item['due_date']))))) ?></td>
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
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cuota <?= esc($item['installment_number']) ?> - generada <?= esc(date('d/m/Y', strtotime($item['generation_date'] ?? date('Y-m-01', strtotime($item['due_date']))))) ?> - vence <?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></p>
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
                No se encontraron cuotas pendientes para los filtros seleccionados.
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
