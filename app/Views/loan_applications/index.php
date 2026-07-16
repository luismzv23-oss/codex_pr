<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Solicitudes</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Embudo base de originacion y evaluacion crediticia.</p>
        </div>
        <?php if (auth()->user()?->can('applications.create')): ?>
            <a href="/solicitudes/crear" class="icon-action <?= icon_button_classes('accent') ?>" title="Nueva solicitud" aria-label="Nueva solicitud">
                <?= app_icon('document-plus') ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <?php foreach (['draft', 'evaluation', 'approved'] as $status): ?>
            <div class="glass-card p-5">
                <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc(status_label($status)) ?></p>
                <p class="mt-3 text-3xl font-semibold">
                    <?= count(array_filter($applications, static fn(array $item): bool => $item['status'] === $status)) ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="get" action="/solicitudes" class="glass-card grid gap-4 p-5 sm:grid-cols-2 md:grid-cols-5 items-end">
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Buscar</span>
            <input type="text" name="buscar" value="<?= esc($buscar) ?>" placeholder="Nombre de cliente..." class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</span>
            <select name="estado" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="draft" <?= $estado === 'draft' ? 'selected' : '' ?>>Borrador</option>
                <option value="evaluation" <?= $estado === 'evaluation' ? 'selected' : '' ?>>En evaluacion</option>
                <option value="approved" <?= $estado === 'approved' ? 'selected' : '' ?>>Aprobado</option>
                <option value="rejected" <?= $estado === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Moneda</span>
            <select name="moneda" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todas</option>
                <option value="ARS" <?= $moneda === 'ARS' ? 'selected' : '' ?>>ARS</option>
                <option value="USD" <?= $moneda === 'USD' ? 'selected' : '' ?>>USD</option>
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sistema</span>
            <select name="sistema" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="french" <?= $sistema === 'french' ? 'selected' : '' ?>>Frances</option>
                <option value="german" <?= $sistema === 'german' ? 'selected' : '' ?>>Aleman</option>
                <option value="american" <?= $sistema === 'american' ? 'selected' : '' ?>>Americano</option>
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950 px-4 py-2.5 text-sm font-medium transition hover:bg-slate-800 dark:hover:bg-slate-100 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <?= app_icon('filter', 'h-4 w-4') ?>
                <span>Filtrar</span>
            </button>
            <?php if ($buscar !== '' || $estado !== '' || $moneda !== '' || $sistema !== ''): ?>
                <a href="/solicitudes" class="inline-flex items-center justify-center p-2.5 rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" title="Limpiar filtros">
                    <?= app_icon('close', 'h-5 w-5') ?>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <div class="grid gap-5 xl:grid-cols-2">
        <?php if ($applications === []): ?>
            <div class="glass-card xl:col-span-2 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                No se encontraron solicitudes para los filtros seleccionados.
            </div>
        <?php else: ?>
            <?php foreach ($applications as $application): ?>
                <a href="/solicitudes/<?= esc($application['guid']) ?>" class="glass-card block p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc($application['customer_name']) ?></p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white"><?= esc(money($application['requested_amount'], $application['currency'])) ?></h2>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?= esc(amortization_system_label($application['amortization_type'])) ?> · <?= esc($application['term_months']) ?> meses · tasa <?= esc(interest_percent($application['interest_rate'])) ?>%</p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($application['status'])) ?>">
                            <?= esc(status_label($application['status'])) ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
