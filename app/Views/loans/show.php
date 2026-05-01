<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div x-data="{
        paymentModal: false,
        deleteModal: false,
        paymentForm: { installmentGuid: '', installmentNumber: '', dueDate: '', amount: '', currency: '<?= esc($loan['currency']) ?>' },
        openPaymentModal(item) {
            this.paymentForm = {
                installmentGuid: item.guid,
                installmentNumber: item.installmentNumber,
                dueDate: item.dueDate,
                amount: item.amount,
                currency: item.currency
            };
            this.paymentModal = true;
        }
    }"
    class="page-transition space-y-6">
    <?php $nextInstallment = null; ?>
    <?php foreach ($installments as $candidate): ?>
        <?php if (! empty($candidate['can_generate_payment'])): ?>
            <?php $nextInstallment = $candidate; ?>
            <?php break; ?>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php $canDeleteLoan = auth()->user()?->can('loans.manage') && ! in_array(($loan['status'] ?? ''), ['paid', 'paid_off'], true) && (float) ($loan['outstanding_balance'] ?? 0) > 0; ?>

    <div class="rounded-[2rem] bg-gradient-to-br from-emerald-700 via-teal-800 to-slate-950 p-8 text-white">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.3em] text-emerald-100">Prestamo</p>
                <h1 class="mt-3 text-4xl font-semibold"><?= esc(money($loan['principal_amount'], $loan['currency'])) ?></h1>
                <p class="mt-2 text-sm text-emerald-100"><?= esc($loan['customer_name']) ?> - <?= esc(amortization_system_label($loan['amortization_type'])) ?> - <?= esc($loan['term_months']) ?> meses</p>
            </div>
            <div class="flex gap-3">
                <span class="inline-flex rounded-full px-4 py-2 text-sm font-medium <?= esc(status_badge($loan['status'])) ?>"><?= esc(status_label($loan['status'])) ?></span>
                <a href="/prestamos/<?= esc($loan['guid']) ?>/pdf" class="icon-action border-white/20 bg-white/10 text-white hover:bg-white/20" title="Descargar PDF del prestamo" aria-label="Descargar PDF del prestamo">
                    <?= app_icon('pdf') ?>
                </a>
                <a href="/prestamos/<?= esc($loan['guid']) ?>/contrato/pdf" class="icon-action border-white/20 bg-white/10 text-white hover:bg-white/20" title="Descargar contrato" aria-label="Descargar contrato">
                    <?= app_icon('statement') ?>
                </a>
                <a href="/prestamos/<?= esc($loan['guid']) ?>/amortizacion" class="icon-action border-white/20 bg-white/10 text-white hover:bg-white/20" title="Ver cronograma" aria-label="Ver cronograma">
                    <?= app_icon('chart') ?>
                </a>
                <?php if (in_array(($loan['status'] ?? ''), ['paid', 'paid_off'], true) || round((float) ($loan['outstanding_balance'] ?? 0), 2) <= 0): ?>
                    <a href="/prestamos/<?= esc($loan['guid']) ?>/libre-deuda/pdf" class="icon-action border-white/20 bg-white/10 text-white hover:bg-white/20" title="Descargar libre deuda" aria-label="Descargar libre deuda">
                        <?= app_icon('approve') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-4">
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Saldo pendiente</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(money($loan['outstanding_balance'], $loan['currency'])) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total a cobrar</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(money($loan['total_payable'], $loan['currency'])) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Proximo vencimiento</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(date('d/m/Y', strtotime($loan['next_due_date']))) ?></p>
        </div>
        <div class="glass-card p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Deuda total del cliente</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(money($customerTotalDebt, $loan['currency'])) ?></p>
        </div>
    </div>

    <div class="glass-card p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Cuotas del prestamo</h2>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        class="icon-action <?= icon_button_classes('ghost') ?> <?= $nextInstallment ? '' : 'pointer-events-none opacity-40' ?>"
                        title="Generar pago"
                        aria-label="Generar pago"
                        @click="openPaymentModal({
                            guid: '<?= esc($nextInstallment['guid'] ?? '') ?>',
                            installmentNumber: '<?= esc((string) ($nextInstallment['installment_number'] ?? '')) ?>',
                            dueDate: '<?= esc((string) ($nextInstallment['due_date'] ?? '')) ?>',
                            amount: '<?= esc((string) ($nextInstallment['amount_due'] ?? '')) ?>',
                            currency: '<?= esc($loan['currency']) ?>'
                        })">
                    <?= app_icon('cash') ?>
                </button>
                <a href="/prestamos/<?= esc($loan['guid']) ?>/estado-cuenta" class="icon-action <?= icon_button_classes('sky') ?>" title="Estado de cuenta" aria-label="Estado de cuenta">
                    <?= app_icon('statement') ?>
                </a>
                <a href="/prestamos/<?= esc($loan['guid']) ?>/amortizacion/pdf" class="icon-action <?= icon_button_classes('ghost') ?>" title="Descargar cronograma PDF" aria-label="Descargar cronograma PDF">
                    <?= app_icon('pdf') ?>
                </a>
                <?php if ($canDeleteLoan): ?>
                    <button type="button" class="icon-action <?= icon_button_classes('rose') ?>" title="Eliminar prestamo" aria-label="Eliminar prestamo" @click="deleteModal = true">
                        <?= app_icon('reject') ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
            <div class="hidden responsive-table lg:block">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900/60">
                        <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                            <th class="px-4 py-3">Nro</th>
                            <th class="px-4 py-3">Vence</th>
                            <th class="px-4 py-3">Monto</th>
                            <th class="px-4 py-3">Pagado</th>
                            <th class="px-4 py-3">Pendiente</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Accion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <?php foreach ($installments as $item): ?>
                            <tr class="text-sm">
                                <td class="px-4 py-3"><?= esc($item['installment_number']) ?></td>
                                <td class="px-4 py-3"><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></td>
                                <td class="px-4 py-3"><?= esc(money($item['total_amount'], $loan['currency'])) ?></td>
                                <td class="px-4 py-3"><?= esc(money($item['paid_amount'], $loan['currency'])) ?></td>
                                <td class="px-4 py-3"><?= esc(money($item['amount_due'] ?? 0, $loan['currency'])) ?></td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($item['status'])) ?>"><?= esc(status_label($item['status'])) ?></span></td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end">
                                        <?php if (! empty($item['can_generate_payment'])): ?>
                                            <button type="button"
                                                    class="icon-action <?= icon_button_classes('emerald') ?>"
                                                    title="Generar pago"
                                                    aria-label="Generar pago"
                                                    @click="openPaymentModal({
                                                        guid: '<?= esc($item['guid']) ?>',
                                                        installmentNumber: '<?= esc((string) $item['installment_number']) ?>',
                                                        dueDate: '<?= esc((string) $item['due_date']) ?>',
                                                        amount: '<?= esc((string) ($item['amount_due'] ?? 0)) ?>',
                                                        currency: '<?= esc($loan['currency']) ?>'
                                                    })">
                                                <?= app_icon('cash') ?>
                                            </button>
                                            <a href="/prestamos/<?= esc($loan['guid']) ?>/cuotas/<?= esc($item['guid']) ?>/pdf" class="icon-action <?= icon_button_classes('ghost') ?>" title="Descargar cuota PDF" aria-label="Descargar cuota PDF">
                                                <?= app_icon('pdf') ?>
                                            </a>
                                        <?php else: ?>
                                            <div class="flex items-center gap-2">
                                                <a href="/prestamos/<?= esc($loan['guid']) ?>/cuotas/<?= esc($item['guid']) ?>/pdf" class="icon-action <?= icon_button_classes('ghost') ?>" title="Descargar cuota PDF" aria-label="Descargar cuota PDF">
                                                    <?= app_icon('pdf') ?>
                                                </a>
                                                <span class="text-xs text-slate-400"><?= $item['status'] === 'paid' ? 'Pagada' : 'Esperando cuota previa' ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid gap-3 p-4 lg:hidden">
                <?php foreach ($installments as $item): ?>
                    <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Cuota <?= esc($item['installment_number']) ?></p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Vence <?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></p>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($item['status'])) ?>"><?= esc(status_label($item['status'])) ?></span>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Monto</p>
                                <p class="font-medium"><?= esc(money($item['total_amount'], $loan['currency'])) ?></p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Pagado</p>
                                <p class="font-medium"><?= esc(money($item['paid_amount'], $loan['currency'])) ?></p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Pendiente</p>
                                <p class="font-medium"><?= esc(money($item['amount_due'] ?? 0, $loan['currency'])) ?></p>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <div class="flex gap-2">
                            <?php if (! empty($item['can_generate_payment'])): ?>
                                <button type="button"
                                        class="icon-action <?= icon_button_classes('emerald') ?>"
                                        title="Generar pago"
                                        aria-label="Generar pago"
                                        @click="openPaymentModal({
                                            guid: '<?= esc($item['guid']) ?>',
                                            installmentNumber: '<?= esc((string) $item['installment_number']) ?>',
                                            dueDate: '<?= esc((string) $item['due_date']) ?>',
                                            amount: '<?= esc((string) ($item['amount_due'] ?? 0)) ?>',
                                            currency: '<?= esc($loan['currency']) ?>'
                                        })">
                                    <?= app_icon('cash') ?>
                                </button>
                            <?php endif; ?>
                                <a href="/prestamos/<?= esc($loan['guid']) ?>/cuotas/<?= esc($item['guid']) ?>/pdf" class="icon-action <?= icon_button_classes('ghost') ?>" title="Descargar cuota PDF" aria-label="Descargar cuota PDF">
                                    <?= app_icon('pdf') ?>
                                </a>
                            </div>
                        </div>
                        <?php if (empty($item['can_generate_payment'])): ?>
                            <p class="mt-3 text-right text-xs text-slate-400"><?= $item['status'] === 'paid' ? 'Pagada' : 'Esperando cuota previa' ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div x-show="paymentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4" @click.self="paymentModal = false">
        <div class="w-full max-w-lg rounded-[2rem] bg-white p-6 shadow-2xl dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-semibold text-slate-900 dark:text-white">Generar pago</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Valida el monto de la cuota. Al procesarlo se reajustan las cuotas restantes.</p>
                </div>
                <button type="button" class="icon-action <?= icon_button_classes('ghost') ?>" @click="paymentModal = false">
                    <?= app_icon('close') ?>
                </button>
            </div>

            <form method="post" action="/pagos/guardar" class="mt-6 space-y-5">
                <?= csrf_field() ?>
                <input type="hidden" name="loan_guid" value="<?= esc($loan['guid']) ?>">
                <input type="hidden" name="customer_guid" value="<?= esc($loan['customer_guid']) ?>">
                <input type="hidden" name="installment_guid" :value="paymentForm.installmentGuid">
                <input type="hidden" name="currency" :value="paymentForm.currency">
                <input type="hidden" name="return_url" value="/prestamos/<?= esc($loan['guid']) ?>">

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <p class="text-slate-500 dark:text-slate-400">Cuota</p>
                        <p class="mt-2 font-semibold text-slate-900 dark:text-white" x-text="paymentForm.installmentNumber"></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <p class="text-slate-500 dark:text-slate-400">Vencimiento</p>
                        <p class="mt-2 font-semibold text-slate-900 dark:text-white" x-text="paymentForm.dueDate"></p>
                    </div>
                </div>

                <label class="space-y-2">
                    <span class="text-sm font-medium">Monto a pagar</span>
                    <input name="amount" x-model="paymentForm.amount" type="number" step="0.01" min="0.01" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">Debe coincidir exactamente con el saldo pendiente de la cuota habilitada.</span>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium">Metodo</span>
                    <select name="payment_method" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                        <?php foreach (['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta', 'check' => 'Cheque'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium">Referencia</span>
                    <input name="reference_number" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium">Notas</span>
                    <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900"></textarea>
                </label>

                <div class="flex items-center justify-between">
                    <button type="button" class="text-sm text-slate-500 hover:text-slate-800 dark:hover:text-white" @click="paymentModal = false">Cancelar</button>
                    <button class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-medium text-white dark:bg-white dark:text-slate-950">
                        <?= app_icon('cash', 'h-4 w-4') ?>
                        <span>Confirmar pago</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($canDeleteLoan): ?>
    <div x-show="deleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4" @click.self="deleteModal = false">
        <div class="w-full max-w-lg rounded-[2rem] bg-white p-6 shadow-2xl dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-semibold text-slate-900 dark:text-white">Eliminar prestamo</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Esta accion elimina el credito y todos sus registros asociados. Para continuar, valida la clave del administrador.</p>
                </div>
                <button type="button" class="icon-action <?= icon_button_classes('ghost') ?>" @click="deleteModal = false">
                    <?= app_icon('close') ?>
                </button>
            </div>

            <form method="post" action="/prestamos/<?= esc($loan['guid']) ?>/eliminar" class="mt-6 space-y-5">
                <?= csrf_field() ?>
                <label class="space-y-2">
                    <span class="text-sm font-medium">Clave del administrador</span>
                    <input type="password" name="admin_password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                </label>

                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
                    Se eliminaran cuotas, pagos, notificaciones y el prestamo. La solicitud volvera a evaluacion para reiniciar el proceso.
                </div>

                <div class="flex items-center justify-between">
                    <button type="button" class="text-sm text-slate-500 hover:text-slate-800 dark:hover:text-white" @click="deleteModal = false">Cancelar</button>
                    <button class="inline-flex items-center gap-2 rounded-2xl bg-rose-600 px-5 py-3 text-sm font-medium text-white hover:bg-rose-500">
                        <?= app_icon('reject', 'h-4 w-4') ?>
                        <span>Eliminar prestamo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
