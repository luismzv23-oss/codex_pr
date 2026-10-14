    <div class="grid gap-5 xl:grid-cols-2">
        <?php foreach ($loans as $loan): ?>
            <a href="/prestamos/<?= esc($loan['guid']) ?>" data-loan-card class="glass-card block p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc($loan['customer_name']) ?></p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white"><?= esc(money($loan['principal_amount'], $loan['currency'])) ?></h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Saldo <?= esc(money($loan['outstanding_balance'], $loan['currency'])) ?> - Proximo vencimiento <?= esc($loan['next_due_date'] ? date('d/m/Y', strtotime($loan['next_due_date'])) : 'Sin vencimiento') ?></p>
                    </div>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($loan['status'])) ?>"><?= esc(status_label($loan['status'])) ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($loans === []): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
            No hay prestamos para el filtro seleccionado.
        </div>
    <?php endif; ?>

    <nav aria-label="Paginación de préstamos" class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-500 dark:text-slate-400" role="status">Mostrando <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?> de <?= (int) $pagination['total'] ?> préstamos</p>
        <div class="flex items-center gap-3">
            <?php $pageUrl = static fn(int $page): string => '/prestamos?' . http_build_query(['estado' => $statusFilter, 'buscar' => $buscar, 'estado_detalle' => $estado_detalle, 'moneda' => $moneda, 'page' => $page]); ?>
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
