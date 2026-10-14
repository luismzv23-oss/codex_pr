<?php if ($groups === []): ?>
    <div class="glass-card p-8 text-center text-sm text-slate-500 dark:text-slate-400">
        No se encontraron registros de pago para los filtros seleccionados.
    </div>
<?php else: ?>
    <?php foreach ($groups as $customer): ?>
        <details data-group-key="<?= esc('customer:' . $customer['guid'], 'attr') ?>" data-customer-group class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3 p-5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-500">
                <span class="payment-chevron inline-block transition-transform" aria-hidden="true">▸</span>
                <span class="flex-1 font-semibold text-slate-900 dark:text-white"><?= esc($customer['name']) ?></span>
                <span class="text-sm text-slate-500 dark:text-slate-400"><?= (int) $customer['loan_count'] ?> préstamos · <?= (int) $customer['payment_count'] ?> pagos</span>
            </summary>
            <div class="space-y-3 border-t border-slate-100 p-3 sm:p-5 dark:border-slate-700">
                <?php foreach ($customer['loans'] as $loan): ?>
                    <details data-group-key="<?= esc('loan:' . $customer['guid'] . ':' . $loan['guid'], 'attr') ?>" data-loan-group class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-600">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3 bg-slate-50 p-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-500 dark:bg-slate-900/50">
                            <span class="payment-chevron inline-block transition-transform" aria-hidden="true">▸</span>
                            <span class="flex-1 font-medium text-slate-900 dark:text-white">Préstamo <?= esc($loan['label']) ?></span>
                            <span class="text-sm text-slate-500 dark:text-slate-400"><?= count($loan['payments']) ?> pagos</span>
                        </summary>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <caption class="sr-only">Pagos de <?= esc($customer['name']) ?> para el préstamo <?= esc($loan['label']) ?></caption>
                                <thead class="bg-slate-50 dark:bg-slate-900/60">
                                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                        <th scope="col" class="px-4 py-3">Monto</th>
                                        <th scope="col" class="px-4 py-3">Método</th>
                                        <th scope="col" class="px-4 py-3">Referencia</th>
                                        <th scope="col" class="px-4 py-3">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    <?php foreach ($loan['payments'] as $payment): ?>
                                        <tr data-payment-row class="text-sm">
                                            <td class="whitespace-nowrap px-4 py-3"><?= esc(money($payment['amount'], $payment['currency'])) ?></td>
                                            <td class="px-4 py-3"><?= esc(['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta', 'check' => 'Cheque'][$payment['payment_method']] ?? ucfirst($payment['payment_method'])) ?></td>
                                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400"><?= esc($payment['reference_number'] ?: '-') ?></td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400"><?= esc(date('d/m/Y H:i', strtotime($payment['created_at']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endforeach; ?>
<?php endif; ?>

<nav aria-label="Paginación de clientes con pagos" class="flex flex-wrap items-center justify-between gap-4">
    <p class="text-sm text-slate-500 dark:text-slate-400" role="status">Mostrando <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?> de <?= (int) $pagination['total'] ?> clientes</p>
    <div class="flex items-center gap-3">
        <?php $pageUrl = static fn(int $page): string => '/pagos?' . http_build_query(['buscar' => $buscar, 'metodo' => $metodo, 'moneda' => $moneda, 'page' => $page]); ?>
        <?php if ($pagination['page'] > 1): ?>
            <a data-page="<?= $pagination['page'] - 1 ?>" href="<?= esc($pageUrl($pagination['page'] - 1), 'attr') ?>" class="rounded-xl border border-slate-300 px-4 py-2 text-sm dark:border-slate-600">Anterior</a>
        <?php else: ?>
            <span aria-disabled="true" class="rounded-xl border border-slate-300 px-4 py-2 text-sm opacity-50 dark:border-slate-600">Anterior</span>
        <?php endif; ?>
        <span class="text-sm text-slate-600 dark:text-slate-300">Página <?= (int) $pagination['page'] ?> de <?= (int) $pagination['pages'] ?></span>
        <?php if ($pagination['page'] < $pagination['pages']): ?>
            <a data-page="<?= $pagination['page'] + 1 ?>" href="<?= esc($pageUrl($pagination['page'] + 1), 'attr') ?>" class="rounded-xl border border-slate-300 px-4 py-2 text-sm dark:border-slate-600">Siguiente</a>
        <?php else: ?>
            <span aria-disabled="true" class="rounded-xl border border-slate-300 px-4 py-2 text-sm opacity-50 dark:border-slate-600">Siguiente</span>
        <?php endif; ?>
    </div>
</nav>
