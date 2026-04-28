<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-3xl page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Nuevo cobro</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Registra una cuenta de transferencia o billetera para recibir pagos.</p>
    </div>

    <form method="post" action="/configuracion/cobros/guardar" class="glass-card space-y-6 p-8">
        <?= csrf_field() ?>
        <div class="grid gap-6 md:grid-cols-2">
            <label class="space-y-2 md:col-span-2">
                <span class="text-sm font-medium">Nombre</span>
                <input name="name" value="<?= old('name') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">CUIT</span>
                <input name="cuit" value="<?= old('cuit') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Entidad</span>
                <input name="entity" value="<?= old('entity') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">CBU</span>
                <input name="cbu" value="<?= old('cbu') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Alias</span>
                <input name="account_alias" value="<?= old('account_alias') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2 md:col-span-2">
                <span class="text-sm font-medium">Estado</span>
                <select name="status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                    <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Activo</option>
                    <option value="disabled" <?= old('status') === 'disabled' ? 'selected' : '' ?>>Deshabilitado</option>
                </select>
            </label>
        </div>

        <div class="flex items-center justify-between">
            <a href="/configuracion/cobros" class="text-sm text-slate-500 hover:text-slate-800 dark:hover:text-white">Volver</a>
            <button class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-medium text-white dark:bg-white dark:text-slate-950">
                <?= app_icon('save', 'h-4 w-4') ?>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
