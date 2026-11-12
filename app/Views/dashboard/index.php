<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-8">
    <?php
        $chartLabels = $dashboard['chart']['labels'] ?? [];
        $chartSeries = $dashboard['chart']['series'] ?? [];
        $hasChartData = false;

        foreach ($chartSeries as $serie) {
            foreach (($serie['data'] ?? []) as $value) {
                if ((float) $value > 0) {
                    $hasChartData = true;
                    break 2;
                }
            }
        }
    ?>
    <section class="rounded-[2rem] bg-gradient-to-br from-slate-950 via-cyan-950 to-emerald-900 p-5 text-white shadow-2xl sm:p-8">
        <p class="text-xs uppercase tracking-[0.3em] text-emerald-200 sm:text-sm">Prestamos lending hub</p>
        <div class="mt-4 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="max-w-3xl text-2xl font-semibold leading-tight sm:text-3xl lg:text-4xl">Base operativa para clientes, solicitudes, prestamos y pagos.</h1>
                <p class="mt-3 max-w-2xl text-sm text-slate-200">El proyecto ya tiene un flujo navegable y un tablero que funciona con base real o con datos demo cuando la BD aun no esta lista.</p>
            </div>
            <div class="flex gap-3 text-sm">
                <a href="/clientes/crear" class="icon-action border-white/20 bg-white/10 text-white hover:bg-white/20" title="Nuevo cliente" aria-label="Nuevo cliente">
                    <?= app_icon('user-plus') ?>
                </a>
                <a href="/solicitudes/crear" class="icon-action border-emerald-300 bg-emerald-300 text-slate-950 hover:bg-emerald-200" title="Nueva solicitud" aria-label="Nueva solicitud">
                    <?= app_icon('document-plus') ?>
                </a>
            </div>
        </div>
    </section>

    <?= view('components/period_filter', ['action' => '/dashboard', 'from' => $collection['from'], 'to' => $collection['to'], 'presets' => $presets]) ?>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Clientes</p>
            <p class="mt-3 break-words text-2xl font-semibold sm:text-3xl"><?= esc($dashboard['stats']['customers']) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Solicitudes pendientes</p>
            <p class="mt-3 break-words text-2xl font-semibold sm:text-3xl"><?= esc($dashboard['stats']['pending_applications']) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Prestamos activos</p>
            <p class="mt-3 break-words text-2xl font-semibold sm:text-3xl"><?= esc($dashboard['stats']['active_loans']) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Ingresos registrados</p>
            <p class="mt-3 break-words text-2xl font-semibold sm:text-3xl"><?= esc(money($dashboard['stats']['monthly_income'])) ?></p>
        </div>
        <?php
            $canSeeReports = auth()->user()?->can('reports.view') ?? false;
            $approvedUrl = '/reportes/dashboard?desde=' . rawurlencode($approvedMonth['from']) . '&hasta=' . rawurlencode($approvedMonth['to']);
            $approvedOpenTag = $canSeeReports
                ? '<a href="' . esc($approvedUrl, 'attr') . '" class="glass-card block p-6" title="Ver reporte de prestamos aprobados">'
                : '<div class="glass-card p-6">';
        ?>
        <?= $approvedOpenTag ?>
            <p class="text-sm text-slate-500 dark:text-slate-400">Aprobados en el periodo</p>
            <p class="mt-3 break-words text-2xl font-semibold sm:text-3xl"><?= esc($approvedMonth['count']) ?></p>
            <?php foreach ($approvedMonth['by_currency'] as $currency => $totals): ?>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= esc(money($totals['principal'], $currency)) ?></p>
            <?php endforeach; ?>
        </<?= $canSeeReports ? 'a' : 'div' ?>>
    </section>

    <?= view('components/collection_kpi', ['collection' => $collection, 'flowLink' => $canSeeReports ? $approvedUrl : null]) ?>

    <section class="grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
        <div class="glass-card min-w-0 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Tendencia de pagos</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Evolucion real de pagos, mora y originacion de prestamos.</p>
                </div>
                <div class="whitespace-nowrap rounded-full bg-rose-500/10 px-3 py-1 text-xs font-medium text-rose-600 dark:text-rose-300">
                    Mora: <?= esc(money($dashboard['stats']['overdue_amount'])) ?>
                </div>
            </div>
            <div id="chart-evolution" class="mt-6"></div>
        </div>

        <?php $activityPages = max(1, (int) ceil(count($dashboard['recent_activity']) / 3)); ?>
        <div class="glass-card flex min-w-0 flex-col p-6" x-data="{ page: 1, pages: <?= $activityPages ?>, perPage: 3 }">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Actividad reciente</h2>
            <div class="mt-6 flex-1 space-y-4">
                <?php if ($dashboard['recent_activity'] === []): ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Sin actividad registrada.</p>
                <?php endif; ?>
                <?php foreach ($dashboard['recent_activity'] as $index => $event): ?>
                    <div class="rounded-2xl border border-slate-200/70 bg-white/70 p-4 dark:border-slate-700 dark:bg-slate-900/40"
                         x-show="Math.floor(<?= (int) $index ?> / perPage) + 1 === page"<?= $index >= 3 ? ' style="display: none"' : '' ?>>
                        <p class="break-words text-sm font-medium text-slate-900 dark:text-white"><?= esc($event['action']) ?></p>
                        <p class="mt-1 break-all text-xs text-slate-500 dark:text-slate-400"><?= esc($event['entity_type']) ?> · <?= esc($event['entity_guid']) ?></p>
                        <p class="mt-2 text-xs text-slate-400 dark:text-slate-500"><?= esc(date('d/m/Y H:i', strtotime($event['created_at']))) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($activityPages > 1): ?>
                <div class="mt-6 flex items-center justify-between gap-2 text-sm">
                    <button type="button" @click="page--" :disabled="page === 1"
                            class="rounded-xl border border-slate-200 px-3 py-1.5 text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                            aria-label="Pagina anterior">&lsaquo; Anterior</button>
                    <span class="text-xs text-slate-500 dark:text-slate-400" x-text="'Pagina ' + page + ' de ' + pages">Pagina 1 de <?= $activityPages ?></span>
                    <button type="button" @click="page++" :disabled="page === pages"
                            class="rounded-xl border border-slate-200 px-3 py-1.5 text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                            aria-label="Pagina siguiente">Siguiente &rsaquo;</button>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const labels = <?= json_encode($chartLabels) ?>;
        const series = <?= json_encode($chartSeries) ?>;

        const chart = new ApexCharts(document.querySelector('#chart-evolution'), {
            series: <?= $hasChartData ? 'series' : "[{ name: 'Sin datos', data: [0] }]" ?>,
            chart: {
                height: 320,
                type: 'area',
                toolbar: { show: false },
                foreColor: '#94a3b8'
            },
            responsive: [{ breakpoint: 640, options: { chart: { height: 260 }, xaxis: { labels: { rotate: -45 } } } }],
            colors: ['#00FF87', '#fb7185', '#38bdf8'],
            stroke: { curve: 'smooth', width: 3 },
            dataLabels: { enabled: false },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.65,
                    opacityTo: 0.08
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return Number(value).toLocaleString('es-AR');
                    }
                }
            },
            xaxis: {
                categories: labels.length ? labels : ['Sin datos']
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return Number(value).toLocaleString('es-AR');
                    }
                }
            }
        });

        chart.render();
    });
</script>
<?= $this->endSection() ?>
