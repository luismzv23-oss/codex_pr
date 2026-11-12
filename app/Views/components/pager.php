<?php
/**
 * Prev/next pager with the same markup as the loans listing.
 * Expects: $pagination (AppRepository::paginationMeta), $baseUrl (path), $noun (plural label), $label (aria label).
 * Links carry data-page so assets/js/ajax-pager.js can load them without a full reload.
 */
$pageUrl = static fn(int $page): string => $baseUrl . '?' . http_build_query(['page' => $page]);
?>
<nav aria-label="<?= esc($label, 'attr') ?>" class="flex flex-wrap items-center justify-between gap-4">
    <p class="text-sm text-slate-500 dark:text-slate-400" role="status">Mostrando <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?> de <?= (int) $pagination['total'] ?> <?= esc($noun) ?></p>
    <div class="flex items-center gap-3">
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
