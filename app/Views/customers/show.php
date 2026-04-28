<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="rounded-[2rem] bg-gradient-to-br from-cyan-900 via-slate-900 to-slate-950 p-8 text-white">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.3em] text-cyan-200">Ficha de cliente</p>
                <h1 class="mt-3 text-4xl font-semibold"><?= esc($customer['full_name']) ?></h1>
                <p class="mt-2 text-sm text-slate-300"><?= esc($customer['email']) ?> · <?= esc($customer['phone'] ?: 'Sin telefono') ?> · DNI <?= esc($customer['dni'] ?? '-') ?></p>
            </div>
            <div class="flex gap-3">
                <span class="inline-flex rounded-full px-4 py-2 text-sm font-medium <?= esc(status_badge($customer['credit_status'] ?? 'active')) ?>"><?= esc(status_label($customer['credit_status'] ?? 'active')) ?></span>
                <a href="/clientes/<?= esc($customer['guid']) ?>/editar" class="icon-action border-white/20 bg-white/10 text-white hover:bg-white/20" title="Editar cliente" aria-label="Editar cliente">
                    <?= app_icon('edit') ?>
                </a>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <div class="glass-card p-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Perfil</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Direccion</dt>
                    <dd><?= esc($customer['address'] ?: 'Sin dato') ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Ingreso estimado</dt>
                    <dd><?= esc(money($customer['estimated_income'] ?? 0)) ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Limite de credito</dt>
                    <dd><?= esc(money($customer['credit_limit'] ?? 0)) ?> (<?= esc($customer['credit_limit_mode'] ?? 'manual') ?>)</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Score de riesgo</dt>
                    <dd><?= esc(number_format((float) $customer['risk_score'], 1)) ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Alta</dt>
                    <dd><?= esc(date('d/m/Y H:i', strtotime($customer['created_at']))) ?></dd>
                </div>
            </dl>
            <div class="mt-6 rounded-2xl bg-slate-100 p-4 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                <?= esc($customer['notes'] ?: 'Sin notas operativas cargadas.') ?>
            </div>
        </div>

        <div class="space-y-6">
            <div class="glass-card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Solicitudes</h2>
                    <a href="/solicitudes/crear?customer_guid=<?= esc($customer['guid']) ?>" class="text-sm text-cyan-600 hover:text-cyan-500">Crear nueva</a>
                </div>
                <div class="mt-5 space-y-3">
                    <?php foreach ($applications as $application): ?>
                        <a href="/solicitudes/<?= esc($application['guid']) ?>" class="block rounded-2xl border border-slate-200 p-4 hover:border-cyan-400 dark:border-slate-700">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-medium"><?= esc(money($application['requested_amount'], $application['currency'])) ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= esc(amortization_system_label($application['amortization_type'])) ?> · <?= esc($application['term_months']) ?> meses</p>
                                </div>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($application['status'])) ?>"><?= esc(status_label($application['status'])) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="glass-card p-6">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Prestamos</h2>
                <div class="mt-5 space-y-3">
                    <?php foreach ($loans as $loan): ?>
                        <a href="/prestamos/<?= esc($loan['guid']) ?>" class="block rounded-2xl border border-slate-200 p-4 hover:border-emerald-400 dark:border-slate-700">
                            <p class="font-medium"><?= esc(money($loan['principal_amount'], $loan['currency'])) ?></p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Saldo: <?= esc(money($loan['outstanding_balance'], $loan['currency'])) ?></p>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($loans === []): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Todavia no tiene prestamos desembolsados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
