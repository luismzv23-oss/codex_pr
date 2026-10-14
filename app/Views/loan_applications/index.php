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

    <div id="applications-summary" aria-live="polite">
        <?= $this->include('loan_applications/_summary') ?>
    </div>

    <form id="applications-filters" method="get" action="/solicitudes" class="glass-card grid gap-4 p-5 sm:grid-cols-2 md:grid-cols-5 items-end">
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

                <a data-clear-filters href="/solicitudes" class="inline-flex items-center justify-center p-2.5 rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" title="Limpiar filtros">
                    <?= app_icon('close', 'h-5 w-5') ?>
                </a>

        </div>
    </form>

    <p id="applications-error" role="alert" class="hidden text-sm text-red-600 dark:text-red-400"></p>
    <div id="applications-list" class="space-y-6" data-page="<?= (int) $pagination['page'] ?>" data-endpoint="<?= esc(site_url('solicitudes/listado'), 'attr') ?>">
        <?= $this->include('loan_applications/_listing') ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script defer src="<?= esc(base_url('assets/js/applications.js'), 'attr') ?>"></script>
<?= $this->endSection() ?>
