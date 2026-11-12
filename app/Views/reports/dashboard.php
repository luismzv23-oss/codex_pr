<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $byCurrency = $approved['by_currency'];
    $kpis = [
        'principal' => 'Capital aprobado',
        'interest' => 'Intereses a cobrar',
        'payable' => 'Total a cobrar',
        'collected' => 'Cobrado',
    ];
    $asOf = date('d/m/Y', strtotime($snapshot['as_of']));
?>
<div class="page-transition space-y-6" x-data="{ tab: '<?= esc($tab, 'js') ?>' }"
     x-init="$watch('tab', function (value) {
         const url = new URL(window.location.href);
         url.searchParams.set('tab', value);
         history.replaceState(null, '', url);
         $nextTick(function () { window.dispatchEvent(new Event('resize')); });
     })">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Metricas globales</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Periodo del <?= esc(date('d/m/Y', strtotime($approved['from']))) ?> al <?= esc(date('d/m/Y', strtotime($approved['to']))) ?>.</p>
    </div>

    <?= view('components/period_filter', ['action' => '/reportes/dashboard', 'from' => $approved['from'], 'to' => $approved['to'], 'presets' => $presets, 'tabModel' => 'tab']) ?>

    <div class="flex flex-wrap gap-2" role="tablist">
        <?php foreach ($tabs as $key => $label): ?>
            <button type="button" role="tab" @click="tab = '<?= esc($key, 'js') ?>'"
                    :aria-selected="tab === '<?= esc($key, 'js') ?>'"
                    :class="tab === '<?= esc($key, 'js') ?>' ? 'border-sky-500 bg-sky-500/10 text-sky-700 dark:text-sky-300' : 'border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'"
                    class="rounded-full border px-4 py-2 text-sm font-medium transition <?= $tab === $key ? 'border-sky-500 bg-sky-500/10 text-sky-700 dark:text-sky-300' : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300' ?>">
                <?= esc($label) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <?php $hidden = static fn(string $key): string => $tab === $key ? '' : ' style="display: none"'; ?>

    <section class="space-y-6" role="tabpanel" x-show="tab === 'situacion'"<?= $hidden('situacion') ?>>
        <p class="text-sm text-slate-500 dark:text-slate-400">Situacion de la cartera al <?= esc($asOf) ?>.</p>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="glass-card p-6">
                <p class="text-sm text-slate-500 dark:text-slate-400">Clientes</p>
                <p class="mt-3 text-3xl font-semibold"><?= esc($snapshot['customers']) ?></p>
            </div>
            <div class="glass-card p-6">
                <p class="text-sm text-slate-500 dark:text-slate-400">Prestamos vigentes</p>
                <p class="mt-3 text-3xl font-semibold"><?= esc($snapshot['active_loans']) ?></p>
                <?php if (count($snapshot['active_loans_by_currency']) > 1): ?>
                    <?php foreach ($snapshot['active_loans_by_currency'] as $currency => $count): ?>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= esc($currency) ?>: <?= esc($count) ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="glass-card p-6">
                <p class="text-sm text-slate-500 dark:text-slate-400">Mora pendiente</p>
                <?php if ($snapshot['overdue_by_currency'] === []): ?>
                    <p class="mt-3 text-3xl font-semibold"><?= esc(money(0)) ?></p>
                <?php endif; ?>
                <?php foreach ($snapshot['overdue_by_currency'] as $currency => $amount): ?>
                    <p class="mt-3 <?= count($snapshot['overdue_by_currency']) > 1 ? 'text-2xl' : 'text-3xl' ?> font-semibold"><?= esc(money($amount, $currency)) ?></p>
                <?php endforeach; ?>
            </div>
            <div class="glass-card p-6">
                <p class="text-sm text-slate-500 dark:text-slate-400">Vencen del <?= esc(date('d/m', strtotime($snapshot['as_of'] . ' +1 day'))) ?> al <?= esc(date('d/m', strtotime($snapshot['upcoming_to']))) ?></p>
                <p class="mt-3 text-3xl font-semibold"><?= esc($snapshot['upcoming_installments']) ?></p>
            </div>
        </div>
    </section>

    <section class="space-y-6" role="tabpanel" x-show="tab === 'aprobados'"<?= $hidden('aprobados') ?>>
        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
            <div class="glass-card p-6">
                <p class="text-sm text-slate-500 dark:text-slate-400">Prestamos aprobados</p>
                <p class="mt-3 text-3xl font-semibold"><?= esc($approved['count']) ?></p>
                <?php foreach ($byCurrency as $currency => $totals): ?>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ticket prom. <?= esc(money($totals['avg_ticket'], $currency)) ?></p>
                <?php endforeach; ?>
            </div>
            <?php foreach ($kpis as $field => $label): ?>
                <div class="glass-card p-6">
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc($label) ?></p>
                    <?php if ($byCurrency === []): ?>
                        <p class="mt-3 text-3xl font-semibold"><?= esc(money(0)) ?></p>
                    <?php endif; ?>
                    <?php foreach ($byCurrency as $currency => $totals): ?>
                        <p class="mt-3 text-2xl font-semibold"><?= esc(money($totals[$field], $currency)) ?></p>
                        <?php if ($field === 'collected'): ?>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= esc(number_format($totals['collected_pct'], 1, ',', '.')) ?>% del total a cobrar</p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="glass-card p-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Capital aprobado por <?= $approved['chart']['granularity'] === 'day' ? 'dia' : 'mes' ?></h2>
            <div id="chart-approved" class="mt-6"></div>
        </div>

        <div class="glass-card overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/60">
                    <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                        <th class="px-4 py-4">Moneda</th>
                        <th class="px-4 py-4">Cantidad</th>
                        <th class="px-4 py-4">Capital</th>
                        <th class="px-4 py-4">Intereses</th>
                        <th class="px-4 py-4">Total a cobrar</th>
                        <th class="px-4 py-4">Cobrado</th>
                        <th class="px-4 py-4">Saldo pendiente hoy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php if ($byCurrency === []): ?>
                        <tr class="text-sm"><td colspan="7" class="px-4 py-6 text-center text-slate-500">No hay prestamos aprobados en el periodo.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($byCurrency as $currency => $totals): ?>
                        <tr class="text-sm">
                            <td class="px-4 py-4 font-medium"><?= esc($currency) ?></td>
                            <td class="px-4 py-4"><?= esc($totals['count']) ?></td>
                            <td class="px-4 py-4"><?= esc(money($totals['principal'], $currency)) ?></td>
                            <td class="px-4 py-4"><?= esc(money($totals['interest'], $currency)) ?></td>
                            <td class="px-4 py-4"><?= esc(money($totals['payable'], $currency)) ?></td>
                            <td class="px-4 py-4"><?= esc(money($totals['collected'], $currency)) ?> <span class="text-xs text-slate-500">(<?= esc(number_format($totals['collected_pct'], 1, ',', '.')) ?>%)</span></td>
                            <td class="px-4 py-4"><?= esc(money($totals['outstanding'], $currency)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="space-y-6" role="tabpanel" x-show="tab === 'cobranza'"<?= $hidden('cobranza') ?>>
        <?= view('components/collection_kpi', ['collection' => $collection, 'flowLink' => null]) ?>

        <div class="glass-card overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/60">
                    <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                        <th class="px-4 py-4">Moneda</th>
                        <th class="px-4 py-4">Esperado</th>
                        <th class="px-4 py-4">Cobrado</th>
                        <th class="px-4 py-4">En mora</th>
                        <th class="px-4 py-4">Por vencer</th>
                        <th class="px-4 py-4">Ingresos (caja)</th>
                        <th class="px-4 py-4">Egresos</th>
                        <th class="px-4 py-4">Neto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php if ($collection['by_currency'] === []): ?>
                        <tr class="text-sm"><td colspan="8" class="px-4 py-6 text-center text-slate-500">Sin cuotas ni movimientos en el periodo.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($collection['by_currency'] as $currency => $totals): ?>
                        <tr class="text-sm">
                            <td class="px-4 py-4 font-medium"><?= esc($currency) ?></td>
                            <td class="px-4 py-4"><?= esc(money($totals['expected'], $currency)) ?></td>
                            <td class="px-4 py-4"><?= esc(money($totals['collected'], $currency)) ?> <span class="text-xs text-slate-500">(<?= esc(number_format($totals['collected_pct'], 1, ',', '.')) ?>%)</span></td>
                            <td class="px-4 py-4 text-rose-600 dark:text-rose-300"><?= esc(money($totals['overdue'], $currency)) ?> <span class="text-xs">(<?= esc(number_format($totals['overdue_pct'], 1, ',', '.')) ?>%)</span></td>
                            <td class="px-4 py-4"><?= esc(money($totals['upcoming'], $currency)) ?></td>
                            <td class="px-4 py-4"><?= esc(money($totals['income'], $currency)) ?></td>
                            <td class="px-4 py-4"><?= esc(money($totals['outflow'], $currency)) ?></td>
                            <td class="px-4 py-4 font-semibold <?= $totals['net'] < 0 ? 'text-rose-600 dark:text-rose-300' : 'text-emerald-600 dark:text-emerald-300' ?>"><?= esc(money($totals['net'], $currency)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="border-t border-slate-200 px-4 py-3 text-right text-sm dark:border-slate-700">
                <a href="/reportes/mora" class="font-medium text-sky-600 hover:underline dark:text-sky-400">Ver detalle de mora &rsaquo;</a>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const labels = <?= json_encode($approved['chart']['labels']) ?>;
        const series = <?= json_encode($approved['chart']['series']) ?>;
        const formatter = function (value) {
            return Number(value).toLocaleString('es-AR');
        };

        new ApexCharts(document.querySelector('#chart-approved'), {
            series: series.length ? series : [{ name: 'Sin datos', data: labels.map(function () { return 0; }) }],
            chart: { height: 320, type: 'bar', toolbar: { show: false }, foreColor: '#94a3b8' },
            colors: ['#38bdf8', '#00FF87', '#fb7185'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: labels },
            yaxis: { labels: { formatter: formatter } },
            tooltip: { y: { formatter: formatter } },
            noData: { text: 'Sin datos' }
        }).render();
    });
</script>
<?= $this->endSection() ?>
