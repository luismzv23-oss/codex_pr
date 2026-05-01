<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-5xl page-transition space-y-6" x-data="{ showSimulation: <?= $simulation !== null ? 'true' : 'false' ?> }">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Simulacion del credito</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Calcula las cuotas del prestamo sin registrar informacion en la base de datos.</p>
    </div>

    <form method="post" action="/simulacion-credito" class="glass-card space-y-6 p-8">
        <?= csrf_field() ?>
        <div class="grid gap-6 md:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-medium">Monto a solicitar</span>
                <input type="number" step="0.01" min="0.01" name="amount" value="<?= esc(old('amount', $input['amount'])) ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Tasa de Interes (%)</span>
                <input type="number" step="0.01" min="0" name="interest_rate" value="<?= esc(old('interest_rate', $input['interest_rate'])) ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Cuotas a pagar</span>
                <input type="number" min="1" name="terms" value="<?= esc(old('terms', $input['terms'])) ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Moneda</span>
                <input name="currency" maxlength="5" value="<?= esc(old('currency', $input['currency'])) ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 uppercase dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2 md:col-span-2">
                <span class="text-sm font-medium">Sistema de amortizacion</span>
                <select name="amortization_type" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                    <?php foreach ($systems as $system): ?>
                        <option value="<?= esc($system['code']) ?>" <?= old('amortization_type', $input['amortization_type']) === $system['code'] ? 'selected' : '' ?>>
                            <?= esc(amortization_system_label($system['code'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="flex justify-end">
            <button class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-medium text-white dark:bg-white dark:text-slate-950">
                <?= app_icon('chart', 'h-4 w-4') ?>
                <span>Simular credito</span>
            </button>
        </div>
    </form>

    <?php if ($simulation !== null): ?>
        <div x-show="showSimulation" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 py-6 backdrop-blur-sm">
            <div class="max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-6 dark:border-slate-700">
                    <div>
                        <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Resultado de la simulacion</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sistema <?= esc($simulation['amortization_label']) ?>. Esta simulacion no crea registros.</p>
                    </div>
                    <button type="button" class="rounded-2xl border border-slate-200 p-3 text-slate-500 hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800" @click="showSimulation = false" title="Cerrar" aria-label="Cerrar">
                        <?= app_icon('close') ?>
                    </button>
                </div>

                <div class="max-h-[calc(90vh-104px)] overflow-y-auto p-6">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Total a pagar</p>
                            <p class="mt-3 text-2xl font-semibold"><?= esc(money($simulation['total_payable'], $simulation['currency'])) ?></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Total intereses</p>
                            <p class="mt-3 text-2xl font-semibold"><?= esc(money($simulation['total_interest'], $simulation['currency'])) ?></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Cuotas</p>
                            <p class="mt-3 text-2xl font-semibold"><?= esc(count($simulation['schedule'])) ?></p>
                        </div>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-900/60">
                                <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                                    <th class="px-4 py-4">Cuota</th>
                                    <th class="px-4 py-4">Capital</th>
                                    <th class="px-4 py-4">Interes</th>
                                    <th class="px-4 py-4">Pago</th>
                                    <th class="px-4 py-4">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <?php foreach ($simulation['schedule'] as $item): ?>
                                    <tr class="text-sm">
                                        <td class="px-4 py-4 font-medium"><?= esc($item['installment_number']) ?></td>
                                        <td class="px-4 py-4"><?= esc(money($item['principal_amount'], $simulation['currency'])) ?></td>
                                        <td class="px-4 py-4"><?= esc(money($item['interest_amount'], $simulation['currency'])) ?></td>
                                        <td class="px-4 py-4"><?= esc(money($item['total_amount'], $simulation['currency'])) ?></td>
                                        <td class="px-4 py-4"><?= esc(money($item['remaining_balance'], $simulation['currency'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
