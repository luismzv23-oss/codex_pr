    <div class="grid gap-5 xl:grid-cols-2">
        <?php if ($applications === []): ?>
            <div class="glass-card xl:col-span-2 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                No se encontraron solicitudes para los filtros seleccionados.
            </div>
        <?php else: ?>
            <?php foreach ($applications as $application): ?>
                <a href="/solicitudes/<?= esc($application['guid']) ?>" data-application-card class="glass-card block p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc($application['customer_name']) ?></p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white"><?= esc(money($application['requested_amount'], $application['currency'])) ?></h2>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?= esc(amortization_system_label($application['amortization_type'])) ?> · <?= esc($application['term_months']) ?> meses · tasa <?= esc(interest_percent($application['interest_rate'])) ?>%</p>
                            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">Total de préstamos del cliente: <strong><?= (int) $application['loan_count'] ?></strong></p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($application['status'])) ?>">
                            <?= esc(status_label($application['status'])) ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <nav aria-label="Paginación de solicitudes" class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-500 dark:text-slate-400" role="status">Mostrando <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?> de <?= (int) $pagination['total'] ?> solicitudes</p>
        <div class="flex items-center gap-3">
            <?php $pageUrl = static fn(int $page): string => '/solicitudes?' . http_build_query(['buscar' => $buscar, 'estado' => $estado, 'moneda' => $moneda, 'sistema' => $sistema, 'page' => $page]); ?>
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
