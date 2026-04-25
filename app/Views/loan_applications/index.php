<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Solicitudes</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Embudo base de originacion y evaluacion crediticia.</p>
        </div>
        <a href="/solicitudes/crear" class="icon-action <?= icon_button_classes('accent') ?>" title="Nueva solicitud" aria-label="Nueva solicitud">
            <?= app_icon('document-plus') ?>
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <?php foreach (['draft', 'evaluation', 'approved'] as $status): ?>
            <div class="glass-card p-5">
                <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc(status_label($status)) ?></p>
                <p class="mt-3 text-3xl font-semibold">
                    <?= count(array_filter($applications, static fn(array $item): bool => $item['status'] === $status)) ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <?php foreach ($applications as $application): ?>
            <a href="/solicitudes/<?= esc($application['guid']) ?>" class="glass-card block p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc($application['customer_name']) ?></p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white"><?= esc(money($application['requested_amount'], $application['currency'])) ?></h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?= esc(amortization_system_label($application['amortization_type'])) ?> · <?= esc($application['term_months']) ?> meses · tasa <?= esc(interest_percent($application['interest_rate'])) ?>%</p>
                    </div>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($application['status'])) ?>">
                        <?= esc(status_label($application['status'])) ?>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
