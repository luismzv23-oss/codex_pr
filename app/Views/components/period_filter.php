<?php
/**
 * GET date-range filter with quick presets.
 * Expects: $action (form URL), $from, $to (Y-m-d), $presets (label => [from, to]).
 * Optional: $tabModel (Alpine variable name) to keep the active tab when filtering.
 */
$tabModel = $tabModel ?? null;
$inputClass = 'w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none';
?>
<form method="get" action="<?= esc($action, 'attr') ?>" class="glass-card flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
    <?php if ($tabModel !== null): ?>
        <input type="hidden" name="tab" :value="<?= esc($tabModel, 'attr') ?>">
    <?php endif; ?>
    <div class="grid flex-1 gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <label class="space-y-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Desde</span>
            <input type="date" name="desde" value="<?= esc($from, 'attr') ?>" class="<?= $inputClass ?>">
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Hasta</span>
            <input type="date" name="hasta" value="<?= esc($to, 'attr') ?>" class="<?= $inputClass ?>">
        </label>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-900 bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-white dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100">
            <?= app_icon('filter', 'h-4 w-4') ?>
            <span>Filtrar</span>
        </button>
    </div>
    <div class="flex flex-wrap gap-2 text-xs">
        <?php foreach ($presets as $label => [$presetFrom, $presetTo]): ?>
            <?php $active = $presetFrom === $from && $presetTo === $to; ?>
            <?php $presetUrl = $action . '?desde=' . rawurlencode($presetFrom) . '&hasta=' . rawurlencode($presetTo); ?>
            <a href="<?= esc($presetUrl, 'attr') ?>"
               <?php if ($tabModel !== null): ?>:href="'<?= esc($presetUrl, 'js') ?>&tab=' + <?= esc($tabModel, 'attr') ?>"<?php endif; ?>
               class="rounded-full border px-3 py-1.5 font-medium transition <?= $active ? 'border-sky-500 bg-sky-500/10 text-sky-700 dark:text-sky-300' : 'border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' ?>">
                <?= esc($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</form>
