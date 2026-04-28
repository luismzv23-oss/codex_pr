<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-5xl space-y-6 page-transition">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white sm:text-3xl">Nuevo cliente</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Alta estructural del legajo comercial y crediticio.</p>
        </div>
        <a href="/clientes" class="icon-action <?= icon_button_classes('ghost') ?>" title="Volver al listado" aria-label="Volver al listado">
            <?= app_icon('back') ?>
        </a>
    </div>

    <form method="post" action="/clientes/guardar" class="glass-card space-y-8 p-5 sm:p-8">
        <?= csrf_field() ?>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Datos principales</h2>
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <label class="space-y-2">
                    <span class="text-sm font-medium">Nombre</span>
                    <input name="first_name" value="<?= old('first_name') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium">Apellido</span>
                    <input name="last_name" value="<?= old('last_name') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium">DNI</span>
                    <input name="dni" value="<?= old('dni') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium">Telefono</span>
                    <input name="phone" value="<?= old('phone') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                </label>
                <label class="space-y-2 xl:col-span-2">
                    <span class="text-sm font-medium">Email</span>
                    <input type="email" name="email" value="<?= old('email') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                </label>
                <label class="space-y-2 md:col-span-2 xl:col-span-3">
                    <span class="text-sm font-medium">Direccion</span>
                    <input name="address" value="<?= old('address') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                </label>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Capa crediticia</h2>
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <label class="space-y-2">
                    <span class="text-sm font-medium">Ingreso estimado</span>
                    <input name="estimated_income" value="<?= old('estimated_income') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium">Modo limite</span>
                    <select name="credit_limit_mode" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                        <option value="manual" <?= old('credit_limit_mode', 'manual') === 'manual' ? 'selected' : '' ?>>Manual</option>
                        <option value="automatic" <?= old('credit_limit_mode') === 'automatic' ? 'selected' : '' ?>>Automatico</option>
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium">Limite de credito</span>
                    <input name="credit_limit" value="<?= old('credit_limit', '0') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium">Estado crediticio</span>
                    <select name="credit_status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                        <option value="active" <?= old('credit_status', 'active') === 'active' ? 'selected' : '' ?>>Activo</option>
                        <option value="restricted" <?= old('credit_status') === 'restricted' ? 'selected' : '' ?>>Restringido</option>
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium">Estado KYC</span>
                    <select name="kyc_status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                        <?php foreach (['pending' => 'Pendiente', 'verified' => 'Verificado', 'rejected' => 'Rechazado'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= old('kyc_status', 'pending') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="space-y-2">
                    <span class="text-sm font-medium">Score de riesgo</span>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">Se calcula automaticamente en escala de 0 a 10 segun la confiabilidad de pagos del cliente.</div>
                </div>
                <label class="space-y-2 md:col-span-2 xl:col-span-3">
                    <span class="text-sm font-medium">Notas</span>
                    <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900"><?= old('notes') ?></textarea>
                </label>
            </div>
        </section>

        <div class="flex items-center justify-end">
            <button class="icon-action <?= icon_button_classes('dark') ?>" title="Guardar cliente" aria-label="Guardar cliente">
                <?= app_icon('save') ?>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
