<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-8">
    <section class="rounded-[2rem] bg-gradient-to-br from-slate-950 via-cyan-950 to-emerald-900 p-8 text-white shadow-2xl">
        <p class="text-sm uppercase tracking-[0.3em] text-emerald-200">Prestamos lending hub</p>
        <div class="mt-4 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="max-w-3xl text-4xl font-semibold leading-tight">Base operativa para clientes, solicitudes, prestamos y pagos.</h1>
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

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Clientes</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($dashboard['stats']['customers']) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Solicitudes pendientes</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($dashboard['stats']['pending_applications']) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Prestamos activos</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($dashboard['stats']['active_loans']) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Ingresos registrados</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(money($dashboard['stats']['monthly_income'])) ?></p>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
        <div class="glass-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Tendencia de pagos</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Serie simple para validar el tablero base.</p>
                </div>
                <div class="rounded-full bg-rose-500/10 px-3 py-1 text-xs font-medium text-rose-600 dark:text-rose-300">
                    Mora: <?= esc(money($dashboard['stats']['overdue_amount'])) ?>
                </div>
            </div>
            <div id="chart-evolution" class="mt-6"></div>
        </div>

        <div class="glass-card p-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Actividad reciente</h2>
            <div class="mt-6 space-y-4">
                <?php foreach ($dashboard['recent_activity'] as $event): ?>
                    <div class="rounded-2xl border border-slate-200/70 bg-white/70 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?= esc($event['action']) ?></p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= esc($event['entity_type']) ?> · <?= esc($event['entity_guid']) ?></p>
                        <p class="mt-2 text-xs text-slate-400 dark:text-slate-500"><?= esc(date('d/m/Y H:i', strtotime($event['created_at']))) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const labels = <?= json_encode($dashboard['chart']['labels']) ?>;
        const values = <?= json_encode($dashboard['chart']['values']) ?>;

        const chart = new ApexCharts(document.querySelector('#chart-evolution'), {
            series: [{ name: 'Pagos', data: values.length ? values : [0] }],
            chart: {
                height: 320,
                type: 'area',
                toolbar: { show: false },
                foreColor: '#94a3b8'
            },
            colors: ['#00FF87'],
            stroke: { curve: 'smooth', width: 3 },
            dataLabels: { enabled: false },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.65,
                    opacityTo: 0.08
                }
            },
            xaxis: {
                categories: labels.length ? labels : ['Sin datos']
            }
        });

        chart.render();
    });
</script>
<?= $this->endSection() ?>
