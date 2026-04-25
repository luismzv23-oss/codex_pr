<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Metricas globales</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Resumen ejecutivo de la base inicial.</p>
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
