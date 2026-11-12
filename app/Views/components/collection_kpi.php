<?php
/**
 * Collection pie chart + cash flow card for a period. Mirrors the "Tendencia de pagos" /
 * "Actividad reciente" cards of the dashboard (same grid, headers, pills and list items).
 * Expects: $collection (AppRepository::getCollectionSummary), optional $flowLink (string|null).
 */
$byCurrency = $collection['by_currency'];
$currencies = array_keys($byCurrency);
$flowLink = $flowLink ?? null;
$period = date('d/m/Y', strtotime($collection['from'])) . ' - ' . date('d/m/Y', strtotime($collection['to']));
$itemClass = 'rounded-2xl border border-slate-200/70 bg-white/70 p-4 dark:border-slate-700 dark:bg-slate-900/40';
?>
<section class="grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
    <div class="glass-card min-w-0 p-6"
         x-data="collectionDonut(<?= esc(json_encode($byCurrency), 'attr') ?>, <?= esc(json_encode($currencies[0] ?? null), 'attr') ?>)">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Cobranza del periodo</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cuotas que vencen entre <?= esc($period) ?><?php if ($byCurrency !== []): ?> · Esperado: <span class="font-medium text-slate-700 dark:text-slate-200" x-text="money(data().expected)"></span><?php endif; ?></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <?php if (count($currencies) > 1): ?>
                    <?php foreach ($currencies as $currency): ?>
                        <button type="button" @click="select('<?= esc($currency, 'js') ?>')"
                                :class="currency === '<?= esc($currency, 'js') ?>' ? 'bg-sky-500/10 text-sky-600 dark:text-sky-300' : 'text-slate-500 dark:text-slate-400'"
                                class="rounded-full px-3 py-1 text-xs font-medium"><?= esc($currency) ?></button>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($byCurrency !== []): ?>
                    <div class="whitespace-nowrap rounded-full bg-rose-500/10 px-3 py-1 text-xs font-medium text-rose-600 dark:text-rose-300">
                        Mora: <span x-text="money(data().overdue)"></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($byCurrency === []): ?>
            <p class="mt-6 text-sm text-slate-500 dark:text-slate-400">No hay cuotas ni movimientos en el periodo.</p>
        <?php else: ?>
            <div x-ref="chart" class="mt-6"></div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                <div class="<?= $itemClass ?>">
                    <p class="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-white"><span class="h-2.5 w-2.5 rounded-full bg-[#00FF87]"></span>Cobrado</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-text="money(data().collected)"></p>
                    <p class="mt-2 text-xs text-slate-400 dark:text-slate-500" x-text="pct(data().collected_pct)"></p>
                </div>
                <div class="<?= $itemClass ?>">
                    <p class="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-white"><span class="h-2.5 w-2.5 rounded-full bg-[#fb7185]"></span>En mora</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-text="money(data().overdue)"></p>
                    <p class="mt-2 text-xs text-slate-400 dark:text-slate-500" x-text="pct(data().overdue_pct)"></p>
                </div>
                <div class="<?= $itemClass ?>">
                    <p class="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-white"><span class="h-2.5 w-2.5 rounded-full bg-[#94a3b8]"></span>Por vencer</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-text="money(data().upcoming)"></p>
                </div>
                <div class="<?= $itemClass ?>">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">Esperado (100%)</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-text="money(data().expected)"></p>
                    <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">Cobrado + En mora + Por vencer</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card min-w-0 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Flujo del periodo</h2>
            <?php if ($flowLink !== null): ?>
                <a href="<?= esc($flowLink, 'attr') ?>" class="rounded-full bg-sky-500/10 px-3 py-1 text-xs font-medium text-sky-600 dark:text-sky-300">Ver reporte</a>
            <?php endif; ?>
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pagos recibidos vs capital de prestamos aprobados.</p>
        <div class="mt-6 space-y-4">
            <?php if ($byCurrency === []): ?>
                <p class="text-sm text-slate-500 dark:text-slate-400">Sin movimientos.</p>
            <?php endif; ?>
            <?php foreach ($byCurrency as $currency => $totals): ?>
                <div class="<?= $itemClass ?>">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">Egresos</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= esc(money($totals['outflow'], $currency)) ?></p>
                </div>
                <div class="<?= $itemClass ?>">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">Ingresos</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= esc(money($totals['income'], $currency)) ?></p>
                </div>
                <div class="<?= $itemClass ?>">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">Neto</p>
                    <p class="mt-1 text-xs font-medium <?= $totals['net'] < 0 ? 'text-rose-600 dark:text-rose-300' : 'text-emerald-600 dark:text-emerald-300' ?>"><?= esc(money($totals['net'], $currency)) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
    window.collectionDonut = window.collectionDonut || function (byCurrency, initial) {
        return {
            currency: initial,
            chart: null,
            data() { return byCurrency[this.currency] || { expected: 0, collected: 0, overdue: 0, upcoming: 0, collected_pct: 0, overdue_pct: 0 }; },
            money(value) { return this.currency + ' ' + Number(value).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            pct(value) { return Number(value).toLocaleString('es-AR', { maximumFractionDigits: 1 }) + '%'; },
            series() { const d = this.data(); return [d.collected, d.overdue, d.upcoming]; },
            select(currency) {
                this.currency = currency;
                if (this.chart) this.chart.updateSeries(this.series());
            },
            init() {
                if (!this.$refs.chart || typeof ApexCharts === 'undefined') return;
                const self = this;
                this.chart = new ApexCharts(this.$refs.chart, {
                    series: this.series(),
                    labels: ['Cobrado', 'En mora', 'Por vencer'],
                    chart: { type: 'pie', height: 320, foreColor: '#94a3b8' },
                    responsive: [{ breakpoint: 640, options: { chart: { height: 260 } } }],
                    colors: ['#00FF87', '#fb7185', '#94a3b8'],
                    legend: { show: false },
                    dataLabels: {
                        enabled: true,
                        formatter: function (value) { return self.pct(value.toFixed(1)); },
                        style: { fontSize: '13px', fontWeight: 600, colors: ['#0f172a'] },
                        dropShadow: { enabled: false }
                    },
                    plotOptions: { pie: { dataLabels: { minAngleToShowLabel: 12 } } },
                    stroke: { width: 2, colors: ['#ffffff'] },
                    tooltip: { y: { formatter: function (value) { return self.money(value); } } },
                    noData: { text: 'Sin datos' }
                });
                this.chart.render();
            }
        };
    };
</script>
