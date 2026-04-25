<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-4xl page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Nueva solicitud</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Captura base para el circuito de evaluacion.</p>
    </div>

    <form method="post" action="/solicitudes/guardar" class="glass-card space-y-6 p-8">
        <?= csrf_field() ?>
        <div class="grid gap-6 md:grid-cols-2">
            <label class="space-y-2 md:col-span-2">
                <span class="text-sm font-medium">Cliente</span>
                <select name="customer_guid" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                    <option value="">Seleccionar cliente</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= esc($customer['guid']) ?>" <?= old('customer_guid') === $customer['guid'] ? 'selected' : '' ?>><?= esc($customer['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Monto solicitado</span>
                <input name="requested_amount" value="<?= old('requested_amount') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Moneda</span>
                <input name="currency" value="<?= old('currency', 'ARS') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 uppercase dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Tasa (%)</span>
                <input type="number" step="0.01" min="0" name="interest_rate" value="<?= old('interest_rate', '15') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                <p class="text-xs text-slate-500 dark:text-slate-400">Ingresa el porcentaje directo. Ejemplo: <strong>15</strong> equivale a <strong>15%</strong>.</p>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Plazo (meses)</span>
                <input name="term_months" value="<?= old('term_months', '12') ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2 md:col-span-2">
                <span class="text-sm font-medium">Sistema de amortizacion</span>
                <select name="amortization_type" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                    <?php foreach ($systems as $system): ?>
                        <option value="<?= esc($system['code']) ?>" <?= old('amortization_type', 'french') === $system['code'] ? 'selected' : '' ?>><?= esc(amortization_system_label($system['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="flex items-center justify-between">
            <a href="/solicitudes" class="text-sm text-slate-500 hover:text-slate-800 dark:hover:text-white">Volver</a>
            <button class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-medium text-white dark:bg-white dark:text-slate-950">
                <?= app_icon('save', 'h-4 w-4') ?>
                <span>Crear solicitud</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
