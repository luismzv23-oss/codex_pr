<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Prestamos</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Desde este modulo se aprueban solicitudes y se visualizan los prestamos ya generados.</p>
    </div>

    <div class="glass-card p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Solicitudes pendientes de aprobacion</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Aprobar una solicitud crea el prestamo y genera sus cuotas segun el sistema elegido.</p>
            </div>
            <span class="rounded-full bg-amber-100 px-4 py-2 text-sm font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"><?= count($approvalQueue) ?> pendientes</span>
        </div>

        <?php if ($approvalQueue !== []): ?>
            <div class="mt-5 grid gap-4 xl:grid-cols-2">
                <?php foreach ($approvalQueue as $application): ?>
                    <a href="/solicitudes/<?= esc($application['guid']) ?>" class="rounded-2xl border border-slate-200 p-5 transition hover:border-sky-400 hover:bg-sky-50/60 dark:border-slate-700 dark:hover:bg-slate-800">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc($application['customer_name']) ?></p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white"><?= esc(money($application['requested_amount'], $application['currency'])) ?></h3>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?= esc(amortization_system_label($application['amortization_type'])) ?> - <?= esc($application['term_months']) ?> meses - tasa <?= esc(interest_percent($application['interest_rate'])) ?>%</p>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($application['status'])) ?>"><?= esc(status_label($application['status'])) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="mt-5 rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                No hay solicitudes pendientes de aprobacion.
            </div>
        <?php endif; ?>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Prestamos generados</h2>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Agrupados por los estados Activo y Cancelado.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/prestamos?estado=active" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm transition <?= ($statusFilter ?? 'active') === 'active' ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800' ?>">
                <span>Activos</span>
            </a>
            <a href="/prestamos?estado=cancelled" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm transition <?= ($statusFilter ?? 'active') === 'cancelled' ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800' ?>">
                <span>Cancelados</span>
            </a>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <?php foreach ($loans as $loan): ?>
            <a href="/prestamos/<?= esc($loan['guid']) ?>" class="glass-card block p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc($loan['customer_name']) ?></p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white"><?= esc(money($loan['principal_amount'], $loan['currency'])) ?></h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Saldo <?= esc(money($loan['outstanding_balance'], $loan['currency'])) ?> - Proximo vencimiento <?= esc($loan['next_due_date'] ? date('d/m/Y', strtotime($loan['next_due_date'])) : 'Sin vencimiento') ?></p>
                    </div>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($loan['status'])) ?>"><?= esc(status_label($loan['status'])) ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($loans === []): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
            No hay prestamos para el filtro seleccionado.
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
