<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div x-data="{ deleteModal: false }" class="page-transition space-y-6">
    <div class="rounded-[2rem] bg-gradient-to-br from-amber-600 via-orange-700 to-slate-950 p-8 text-white">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.3em] text-amber-100">Solicitud</p>
                <h1 class="mt-3 text-4xl font-semibold"><?= esc(money($application['requested_amount'], $application['currency'])) ?></h1>
                <p class="mt-2 text-sm text-amber-100"><?= esc($application['customer_name']) ?> - <?= esc(amortization_system_label($application['amortization_type'])) ?> - <?= esc($application['term_months']) ?> meses</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex rounded-full px-4 py-2 text-sm font-medium <?= esc(status_badge($application['status'])) ?>"><?= esc(status_label($application['status'])) ?></span>
                <?php if (($application['status'] ?? '') === 'rejected' && auth()->user()?->can('applications.manage')): ?>
                    <button type="button" class="icon-action <?= icon_button_classes('rose') ?>" title="Eliminar solicitud" aria-label="Eliminar solicitud" @click="deleteModal = true">
                        <?= app_icon('reject') ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <div class="glass-card p-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Resumen</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Tasa</dt>
                    <dd><?= esc(interest_percent($application['interest_rate'])) ?>%</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Monto aprobado</dt>
                    <dd><?= esc(money($application['approved_amount'] ?? 0, $application['currency'])) ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Alta</dt>
                    <dd><?= esc(date('d/m/Y H:i', strtotime($application['created_at']))) ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Generacion del prestamo</dt>
                    <dd><?= esc($application['disbursed_at'] ? date('d/m/Y H:i', strtotime($application['disbursed_at'])) : 'Pendiente') ?></dd>
                </div>
            </dl>

            <?php if (! empty($application['linked_loan_guid'])): ?>
                <div class="mt-6 flex flex-wrap gap-2">
                    <?php if (auth()->user()?->can('loans.view')): ?>
                    <a href="/prestamos/<?= esc($application['linked_loan_guid']) ?>" class="inline-flex items-center gap-2 rounded-2xl border <?= icon_button_classes('sky') ?> px-4 py-3 text-sm font-medium">
                        <?= app_icon('loan', 'h-4 w-4') ?>
                        <span>Ver prestamo generado</span>
                    </a>
                    <?php endif; ?>
                    <?php if (auth()->user()?->can('documents.download')): ?>
                    <a href="/prestamos/<?= esc($application['linked_loan_guid']) ?>/contrato/pdf" class="inline-flex items-center gap-2 rounded-2xl border <?= icon_button_classes('ghost') ?> px-4 py-3 text-sm font-medium">
                        <?= app_icon('pdf', 'h-4 w-4') ?>
                        <span>Descargar contrato</span>
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($application['rejection_reason'])): ?>
                <div class="mt-6 rounded-2xl bg-rose-50 p-4 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-200">
                    <?= esc($application['rejection_reason']) ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (auth()->user()?->can('applications.manage')): ?>
        <div class="glass-card p-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Proceso de aprobacion</h2>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Al aprobar se crea el prestamo y se generan automaticamente sus cuotas.</p>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <form method="post" action="/solicitudes/<?= esc($application['guid']) ?>/evaluar" class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <?= csrf_field() ?>
                    <p class="text-sm font-medium">Enviar a evaluacion</p>
                    <button class="icon-action mt-4 <?= icon_button_classes('sky') ?>" title="Enviar a evaluacion" aria-label="Enviar a evaluacion">
                        <?= app_icon('chart') ?>
                    </button>
                </form>

                <form method="post" action="/solicitudes/<?= esc($application['guid']) ?>/aprobar" class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <?= csrf_field() ?>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Monto aprobado</span>
                        <input name="approved_amount" value="<?= esc($application['approved_amount'] ?? $application['requested_amount']) ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                    </label>
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Este paso genera el prestamo en el menu de prestamos y crea sus cuotas.</p>
                    <button class="icon-action mt-4 <?= icon_button_classes('emerald') ?>" title="Aprobar y generar prestamo" aria-label="Aprobar y generar prestamo">
                        <?= app_icon('approve') ?>
                    </button>
                </form>

                <form method="post" action="/solicitudes/<?= esc($application['guid']) ?>/rechazar" class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <?= csrf_field() ?>
                    <label class="space-y-2 text-sm">
                        <span class="font-medium">Motivo</span>
                        <textarea name="rejection_reason" rows="3" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">Capacidad de pago insuficiente</textarea>
                    </label>
                    <button class="icon-action mt-4 <?= icon_button_classes('rose') ?>" title="Rechazar solicitud" aria-label="Rechazar solicitud">
                        <?= app_icon('reject') ?>
                    </button>
                </form>

                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <p class="text-sm font-medium">Desembolso separado</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">En esta version, el prestamo queda generado al aprobar. No hay paso extra de desembolso.</p>
                    <div class="icon-action mt-4 <?= icon_button_classes('ghost') ?>">
                        <?= app_icon('loan') ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (($application['status'] ?? '') === 'rejected' && auth()->user()?->can('applications.manage')): ?>
        <div x-show="deleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4" @click.self="deleteModal = false">
            <div class="w-full max-w-lg rounded-[2rem] bg-white p-6 shadow-2xl dark:bg-slate-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-900 dark:text-white">Eliminar solicitud rechazada</h3>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Para reiniciar desde cero, valida la clave del administrador antes de borrar esta solicitud.</p>
                    </div>
                    <button type="button" class="icon-action <?= icon_button_classes('ghost') ?>" @click="deleteModal = false">
                        <?= app_icon('close') ?>
                    </button>
                </div>

                <form method="post" action="/solicitudes/<?= esc($application['guid']) ?>/eliminar" class="mt-6 space-y-5">
                    <?= csrf_field() ?>
                    <label class="space-y-2">
                        <span class="text-sm font-medium">Clave del administrador</span>
                        <input type="password" name="admin_password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                    </label>

                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
                        Esta accion elimina definitivamente la solicitud rechazada y sus trazas relacionadas.
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="button" class="text-sm text-slate-500 hover:text-slate-800 dark:hover:text-white" @click="deleteModal = false">Cancelar</button>
                        <button class="inline-flex items-center gap-2 rounded-2xl bg-rose-600 px-5 py-3 text-sm font-medium text-white hover:bg-rose-500">
                            <?= app_icon('reject', 'h-4 w-4') ?>
                            <span>Eliminar solicitud</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
