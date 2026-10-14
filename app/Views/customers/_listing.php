    <div class="hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800 lg:block">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-900/60">
                <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                    <th class="px-6 py-4">Cliente</th>
                    <th class="px-6 py-4">DNI</th>
                    <th class="px-6 py-4">Estado</th>
                    <th class="px-6 py-4">Limite</th>
                    <th class="px-6 py-4">Total de préstamos</th>
                    <th class="px-6 py-4">Alta</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                <?php if ($customers === []): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
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
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?= (int) $customer['loan_count'] ?></td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400"><?= esc(date('d/m/Y', strtotime($customer['created_at']))) ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="/clientes/<?= esc($customer['guid']) ?>" class="icon-action <?= icon_button_classes('ghost') ?>" title="Ver ficha" aria-label="Ver ficha">
                                    <?= app_icon('view') ?>
                                </a>
                                <?php if (auth()->user()?->can('customers.delete')): ?>
                                <button type="button" data-delete-customer data-preview-url="<?= esc(site_url('clientes/' . $customer['guid'] . '/eliminacion'), 'attr') ?>" data-delete-url="<?= esc(site_url('clientes/' . $customer['guid']), 'attr') ?>" class="icon-action <?= icon_button_classes('rose') ?>" title="Eliminar cliente" aria-label="Eliminar cliente">
                                    <?= app_icon('reject') ?>
                                </button>
                                <?php endif; ?>
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
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Total de préstamos: <?= (int) $customer['loan_count'] ?></p>
                        </div>
                        <a href="/clientes/<?= esc($customer['guid']) ?>" class="icon-action <?= icon_button_classes('ghost') ?>" title="Ver ficha" aria-label="Ver ficha">
                            <?= app_icon('view') ?>
                        </a>
                        <?php if (auth()->user()?->can('customers.delete')): ?>
                        <button type="button" data-delete-customer data-preview-url="<?= esc(site_url('clientes/' . $customer['guid'] . '/eliminacion'), 'attr') ?>" data-delete-url="<?= esc(site_url('clientes/' . $customer['guid']), 'attr') ?>" class="icon-action <?= icon_button_classes('rose') ?>" title="Eliminar cliente" aria-label="Eliminar cliente">
                            <?= app_icon('reject') ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <nav aria-label="Paginación de clientes" class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-500 dark:text-slate-400" role="status">Mostrando <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?> de <?= (int) $pagination['total'] ?> clientes</p>
        <div class="flex items-center gap-3">
            <?php $pageUrl = static fn(int $page): string => '/clientes?' . http_build_query(['buscar' => $buscar, 'estado_crediticio' => $estado_crediticio, 'estado_kyc' => $estado_kyc, 'page' => $page]); ?>
            <?php if ($pagination['page'] > 1): ?>
                <a data-page="<?= $pagination['page'] - 1 ?>" href="<?= esc($pageUrl($pagination['page'] - 1), 'attr') ?>" class="rounded-xl border border-slate-300 px-4 py-2 text-sm dark:border-slate-600">Anterior</a>
            <?php else: ?>
                <span aria-disabled="true" class="rounded-xl border border-slate-300 px-4 py-2 text-sm opacity-50 dark:border-slate-600">Anterior</span>
            <?php endif; ?>
            <span class="text-sm text-slate-600 dark:text-slate-300">Página <?= (int) $pagination['page'] ?> de <?= (int) $pagination['pages'] ?></span>
            <?php if ($pagination['page'] < $pagination['pages']): ?>
                <a data-page="<?= $pagination['page'] + 1 ?>" href="<?= esc($pageUrl($pagination['page'] + 1), 'attr') ?>" class="rounded-xl border border-slate-300 px-4 py-2 text-sm dark:border-slate-600">Siguiente</a>
            <?php else: ?>
                <span aria-disabled="true" class="rounded-xl border border-slate-300 px-4 py-2 text-sm opacity-50 dark:border-slate-600">Siguiente</span>
            <?php endif; ?>
        </div>
    </nav>
