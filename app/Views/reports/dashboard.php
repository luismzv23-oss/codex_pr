<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $byCurrency = $approved['by_currency'];
    $inputClass = 'w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none';
    $kpis = [
        'principal' => 'Capital aprobado',
        'interest' => 'Intereses a cobrar',
        'payable' => 'Total a cobrar',
        'collected' => 'Cobrado',
    ];
?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Metricas globales</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Prestamos aprobados entre el <?= esc(date('d/m/Y', strtotime($approved['from']))) ?> y el <?= esc(date('d/m/Y', strtotime($approved['to']))) ?>.</p>
    </div>

    <form method="get" action="/reportes/dashboard" class="glass-card flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="grid flex-1 gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <label class="space-y-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Desde</span>
                <input type="date" name="desde" value="<?= esc($approved['from'], 'attr') ?>" class="<?= $inputClass ?>">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Hasta</span>
                <input type="date" name="hasta" value="<?= esc($approved['to'], 'attr') ?>" class="<?= $inputClass ?>">
            </label>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-900 bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-white dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100">
                <?= app_icon('filter', 'h-4 w-4') ?>
                <span>Filtrar</span>
            </button>
        </div>
        <div class="flex flex-wrap gap-2 text-xs">
            <?php foreach ($presets as $label => [$presetFrom, $presetTo]): ?>
                <?php $active = $presetFrom === $approved['from'] && $presetTo === $approved['to']; ?>
                <a href="/reportes/dashboard?desde=<?= esc($presetFrom, 'url') ?>&hasta=<?= esc($presetTo, 'url') ?>"
                   class="rounded-full border px-3 py-1.5 font-medium transition <?= $active ? 'border-sky-500 bg-sky-500/10 text-sky-700 dark:text-sky-300' : 'border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' ?>">
                    <?= esc($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </form>

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

    <div>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Situacion actual</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Indicadores generales, no dependen del periodo seleccionado.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Clientes</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($dashboard['stats']['customers']) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Prestamos activos</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($dashboard['stats']['active_loans']) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Mora acumulada</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(money($dashboard['stats']['overdue_amount'])) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Vencimientos proximos</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($dashboard['stats']['upcoming_installments']) ?></p>
        </div>
    </div>
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
