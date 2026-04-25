<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-4xl page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Registrar pago</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Procesa el cobro sobre la cuota habilitada y descuenta la deuda del prestamo.</p>
    </div>

    <form method="post" action="/pagos/guardar" class="glass-card space-y-6 p-8">
        <?= csrf_field() ?>
        <input type="hidden" name="return_url" value="<?= esc($return_url ?? '/pagos') ?>">

        <div class="grid gap-6 md:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-medium">Prestamo</span>
                <?php if ($loan): ?>
                    <input type="hidden" name="loan_guid" value="<?= esc($loan['guid']) ?>">
                    <div class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <?= esc($loan['customer_name']) ?> - <?= esc(money($loan['principal_amount'], $loan['currency'])) ?>
                    </div>
                <?php else: ?>
                    <select name="loan_guid" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                        <option value="">Seleccionar prestamo</option>
                        <?php foreach ($loans as $loan): ?>
                            <option value="<?= esc($loan['guid']) ?>" <?= old('loan_guid', $installment['loan_guid'] ?? '') === $loan['guid'] ? 'selected' : '' ?>>
                                <?= esc($loan['customer_name']) ?> - <?= esc(money($loan['principal_amount'], $loan['currency'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </label>

            <label class="space-y-2">
                <span class="text-sm font-medium">Cliente</span>
                <?php if ($customer): ?>
                    <input type="hidden" name="customer_guid" value="<?= esc($customer['guid']) ?>">
                    <div class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <?= esc($customer['full_name']) ?>
                    </div>
                <?php else: ?>
                    <select name="customer_guid" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                        <option value="">Seleccionar cliente</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= esc($customer['guid']) ?>" <?= old('customer_guid') === $customer['guid'] ? 'selected' : '' ?>><?= esc($customer['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </label>

            <label class="space-y-2">
                <span class="text-sm font-medium">Cuota</span>
                <input type="hidden" name="installment_guid" value="<?= esc(old('installment_guid', $installment_guid ?: '')) ?>">
                <div class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <?= $installment ? 'Cuota ' . esc($installment['installment_number']) . ' - vence ' . esc(date('d/m/Y', strtotime($installment['due_date']))) : esc(old('installment_guid', $installment_guid ?: '')) ?>
                </div>
            </label>

            <label class="space-y-2">
                <span class="text-sm font-medium">Monto a cobrar</span>
                <input name="amount" value="<?= esc(old('amount', $installment['amount_due'] ?? $installment['total_amount'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>

            <label class="space-y-2">
                <span class="text-sm font-medium">Moneda</span>
                <input name="currency" value="<?= esc(old('currency', $loan['currency'] ?? 'ARS')) ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 uppercase dark:border-slate-700 dark:bg-slate-900" required>
            </label>

            <label class="space-y-2">
                <span class="text-sm font-medium">Metodo</span>
                <select name="payment_method" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                    <?php foreach (['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta', 'check' => 'Cheque'] as $value => $label): ?>
                        <option value="<?= esc($value) ?>" <?= old('payment_method', 'transfer') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="space-y-2 md:col-span-2">
                <span class="text-sm font-medium">Referencia</span>
                <input name="reference_number" value="<?= esc(old('reference_number')) ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
            </label>

            <label class="space-y-2 md:col-span-2">
                <span class="text-sm font-medium">Notas</span>
                <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900"><?= esc(old('notes')) ?></textarea>
            </label>
        </div>

        <?php if ($installment): ?>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-700 dark:bg-slate-950">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Monto cuota</p>
                    <p class="mt-2 text-xl font-semibold"><?= esc(money($installment['total_amount'], $loan['currency'] ?? 'ARS')) ?></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-700 dark:bg-slate-950">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pagado</p>
                    <p class="mt-2 text-xl font-semibold"><?= esc(money($installment['paid_amount'], $loan['currency'] ?? 'ARS')) ?></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-700 dark:bg-slate-950">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Saldo pendiente</p>
                    <p class="mt-2 text-xl font-semibold"><?= esc(money($installment['amount_due'] ?? 0, $loan['currency'] ?? 'ARS')) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between">
            <a href="<?= esc($return_url ?? '/pagos') ?>" class="text-sm text-slate-500 hover:text-slate-800 dark:hover:text-white">Volver</a>
            <button class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-medium text-white dark:bg-white dark:text-slate-950">
                <?= app_icon('cash', 'h-4 w-4') ?>
                <span>Procesar pago</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
