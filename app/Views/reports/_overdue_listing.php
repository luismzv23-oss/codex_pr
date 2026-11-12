<?php
/** Overdue installments grouped customer → loan. Same structure and classes as payments/_listing.php. */
$owedText = static function (array $byCurrency): string {
    return implode(' · ', array_map(static fn(string $currency, float $amount): string => money($amount, $currency), array_keys($byCurrency), $byCurrency));
};
$statusBadge = [
    'none' => ['Sin pago', 'bg-rose-500/10 text-rose-600 dark:text-rose-300'],
    'partial' => ['Pago parcial', 'bg-amber-500/10 text-amber-600 dark:text-amber-300'],
];
?>
<?php if ($groups === []): ?>
    <div class="glass-card p-8 text-center text-sm text-slate-500 dark:text-slate-400">
        No hay cuotas vencidas.
    </div>
<?php else: ?>
    <?php foreach ($groups as $customer): ?>
        <details data-group-key="<?= esc('customer:' . $customer['guid'], 'attr') ?>" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3 p-5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-500">
                <span class="payment-chevron inline-block transition-transform" aria-hidden="true">▸</span>
                <span class="flex-1 font-semibold text-slate-900 dark:text-white"><?= esc($customer['name']) ?></span>
                <span class="text-sm text-slate-500 dark:text-slate-400"><?= (int) $customer['loan_count'] ?> préstamos · <?= (int) $customer['installment_count'] ?> cuotas</span>
                <span class="rounded-full bg-rose-500/10 px-3 py-1 text-xs font-medium text-rose-600 dark:text-rose-300">Debe <?= esc($owedText($customer['owed_by_currency'])) ?></span>
            </summary>
            <div class="space-y-3 border-t border-slate-100 p-3 sm:p-5 dark:border-slate-700">
                <?php foreach ($customer['loans'] as $loan): ?>
                    <details data-group-key="<?= esc('loan:' . $customer['guid'] . ':' . $loan['guid'], 'attr') ?>" class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-600">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3 bg-slate-50 p-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-500 dark:bg-slate-900/50">
                            <span class="payment-chevron inline-block transition-transform" aria-hidden="true">▸</span>
                            <span class="flex-1 font-medium text-slate-900 dark:text-white">Préstamo <?= esc($loan['label']) ?> <span class="text-xs text-slate-500">(<?= esc($loan['currency']) ?>)</span></span>
                            <span class="text-sm text-slate-500 dark:text-slate-400"><?= count($loan['installments']) ?> cuotas vencidas · Debe <?= esc(money($loan['owed'], $loan['currency'])) ?></span>
                        </summary>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <caption class="sr-only">Cuotas vencidas de <?= esc($customer['name']) ?> para el préstamo <?= esc($loan['label']) ?></caption>
                                <thead class="bg-slate-50 dark:bg-slate-900/60">
                                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                        <th scope="col" class="px-4 py-3">Cuota</th>
                                        <th scope="col" class="px-4 py-3">Vencimiento</th>
                                        <th scope="col" class="px-4 py-3">Días de atraso</th>
                                        <th scope="col" class="px-4 py-3">Total</th>
                                        <th scope="col" class="px-4 py-3">Pagado</th>
                                        <th scope="col" class="px-4 py-3">Debe</th>
                                        <th scope="col" class="px-4 py-3">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    <?php foreach ($loan['installments'] as $item): ?>
                                        <?php [$statusLabel, $statusClass] = $statusBadge[$item['payment_status']]; ?>
                                        <tr class="text-sm">
                                            <td class="px-4 py-3"><?= esc($item['installment_number']) ?></td>
                                            <td class="whitespace-nowrap px-4 py-3"><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></td>
                                            <td class="px-4 py-3"><?= (int) $item['days_late'] ?></td>
                                            <td class="whitespace-nowrap px-4 py-3"><?= esc(money($item['total'], $loan['currency'])) ?></td>
                                            <td class="whitespace-nowrap px-4 py-3"><?= esc(money($item['paid'], $loan['currency'])) ?></td>
                                            <td class="whitespace-nowrap px-4 py-3 font-medium"><?= esc(money($item['owed'], $loan['currency'])) ?></td>
                                            <td class="px-4 py-3"><span class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium <?= $statusClass ?>"><?= esc($statusLabel) ?></span></td>
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

<?= view('components/pager', ['pagination' => $pagination, 'baseUrl' => '/reportes/mora', 'query' => ['cliente' => $cliente ?? ''], 'noun' => 'clientes en mora', 'label' => 'Paginación de clientes en mora']) ?>
