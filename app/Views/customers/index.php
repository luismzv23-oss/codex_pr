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

    <div class="hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800 lg:block">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-900/60">
                <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                    <th class="px-6 py-4">Cliente</th>
                    <th class="px-6 py-4">DNI</th>
                    <th class="px-6 py-4">Estado</th>
                    <th class="px-6 py-4">Limite</th>
                    <th class="px-6 py-4">Alta</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                <?php foreach ($customers as $customer): ?>
                    <tr class="text-sm">
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-900 dark:text-white"><?= esc($customer['full_name']) ?></p>
                            <p class="text-slate-500 dark:text-slate-400"><?= esc($customer['email']) ?></p>
                        </td>
                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?= esc($customer['dni'] ?? '-') ?></td>
                        <td class="px-6 py-4">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($customer['credit_status'] ?? 'active')) ?>">
                                <?= esc(status_label($customer['credit_status'] ?? 'active')) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?= esc(money($customer['credit_limit'] ?? 0)) ?></td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400"><?= esc(date('d/m/Y', strtotime($customer['created_at']))) ?></td>
                        <td class="px-6 py-4 text-right">
                            <a href="/clientes/<?= esc($customer['guid']) ?>" class="icon-action <?= icon_button_classes('ghost') ?>" title="Ver ficha" aria-label="Ver ficha">
                                <?= app_icon('view') ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="grid gap-4 lg:hidden">
        <?php foreach ($customers as $customer): ?>
            <article class="glass-card p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white"><?= esc($customer['full_name']) ?></h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= esc($customer['dni'] ?? '-') ?> · <?= esc($customer['email']) ?></p>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Limite <?= esc(money($customer['credit_limit'] ?? 0)) ?></p>
                    </div>
                    <a href="/clientes/<?= esc($customer['guid']) ?>" class="icon-action <?= icon_button_classes('ghost') ?>" title="Ver ficha" aria-label="Ver ficha">
                        <?= app_icon('view') ?>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
