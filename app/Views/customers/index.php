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
    <form method="get" action="/clientes" class="glass-card grid gap-4 p-5 sm:grid-cols-2 md:grid-cols-4 items-end">
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Buscar</span>
            <input type="text" name="buscar" value="<?= esc($buscar) ?>" placeholder="Nombre, email, DNI..." class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado Crediticio</span>
            <select name="estado_crediticio" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="active" <?= $estado_crediticio === 'active' ? 'selected' : '' ?>>Activo</option>
                <option value="restricted" <?= $estado_crediticio === 'restricted' ? 'selected' : '' ?>>Restringido</option>
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado KYC</span>
            <select name="estado_kyc" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="pending" <?= $estado_kyc === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                <option value="verified" <?= $estado_kyc === 'verified' ? 'selected' : '' ?>>Verificado</option>
                <option value="rejected" <?= $estado_kyc === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950 px-4 py-2.5 text-sm font-medium transition hover:bg-slate-800 dark:hover:bg-slate-100 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <?= app_icon('filter', 'h-4 w-4') ?>
                <span>Filtrar</span>
            </button>
            <?php if ($buscar !== '' || $estado_crediticio !== '' || $estado_kyc !== ''): ?>
                <a href="/clientes" class="inline-flex items-center justify-center p-2.5 rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" title="Limpiar filtros">
                    <?= app_icon('close', 'h-5 w-5') ?>
                </a>
            <?php endif; ?>
        </div>
    </form>

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
                <?php if ($customers === []): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            No se encontraron clientes para los filtros seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="grid gap-4 lg:hidden">
        <?php if ($customers === []): ?>
            <div class="glass-card p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                No se encontraron clientes para los filtros seleccionados.
            </div>
        <?php else: ?>
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
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
