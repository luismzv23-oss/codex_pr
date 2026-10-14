<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Clientes</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Base inicial de onboarding, KYC y perfil de riesgo.</p>
        </div>
        <a href="/clientes/crear" class="icon-action <?= icon_button_classes('accent') ?>" title="Nuevo cliente" aria-label="Nuevo cliente">
            <?= app_icon('user-plus') ?>
        </a>
    </div>
    <form id="customers-filters" method="get" action="/clientes" class="glass-card grid gap-4 p-5 sm:grid-cols-2 md:grid-cols-4 items-end">
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Buscar</span>
            <input type="text" name="buscar" value="<?= esc($buscar) ?>" placeholder="Nombre, email, DNI..." class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado Crediticio</span>
            <select name="estado_crediticio" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="active" <?= $estado_crediticio === 'active' ? 'selected' : '' ?>>Activo</option>
                <option value="restricted" <?= $estado_crediticio === 'restricted' ? 'selected' : '' ?>>Restringido</option>
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado KYC</span>
            <select name="estado_kyc" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="pending" <?= $estado_kyc === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                <option value="verified" <?= $estado_kyc === 'verified' ? 'selected' : '' ?>>Verificado</option>
                <option value="rejected" <?= $estado_kyc === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950 px-4 py-2.5 text-sm font-medium transition hover:bg-slate-800 dark:hover:bg-slate-100 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <?= app_icon('filter', 'h-4 w-4') ?>
                <span>Filtrar</span>
            </button>

                <a data-clear-filters href="/clientes" class="inline-flex items-center justify-center p-2.5 rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" title="Limpiar filtros">
                    <?= app_icon('close', 'h-5 w-5') ?>
                </a>

        </div>
    </form>

    <p id="customers-message" role="status" class="hidden text-sm text-slate-700 dark:text-slate-200"></p>
    <p id="customers-error" role="alert" class="hidden text-sm text-red-600 dark:text-red-400"></p>
    <div id="customers-list" class="space-y-6" data-page="<?= (int) $pagination['page'] ?>" data-endpoint="<?= esc(site_url('clientes/listado'), 'attr') ?>">
        <?= $this->include('customers/_listing') ?>
    </div>
</div>
<?= $this->include('customers/_delete_dialog') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script defer src="<?= esc(base_url('assets/js/customer-deletion.js'), 'attr') ?>"></script>
<script defer src="<?= esc(base_url('assets/js/customers.js'), 'attr') ?>"></script>
<?= $this->endSection() ?>
