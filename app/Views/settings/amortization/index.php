<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Sistemas de amortizacion</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Catalogo editable para definir sistemas de cobro disponibles.</p>
        </div>
        <a href="/configuracion/amortizacion/crear" class="inline-flex items-center gap-2 rounded-2xl border <?= icon_button_classes('dark') ?> px-4 py-3 text-sm font-medium">
            <?= app_icon('add', 'h-4 w-4') ?>
        </a>
    </div>

    <div class="glass-card overflow-hidden p-2">
        <div class="hidden responsive-table lg:block">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/60">
                    <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                        <th class="px-4 py-4">Codigo</th>
                        <th class="px-4 py-4">Nombre</th>
                        <th class="px-4 py-4">Descripcion</th>
                        <th class="px-4 py-4">Estado</th>
                        <th class="px-4 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php foreach ($systems as $system): ?>
                        <tr class="text-sm">
                            <td class="px-4 py-4 font-medium"><?= esc($system['code']) ?></td>
                            <td class="px-4 py-4"><?= esc($system['name']) ?></td>
                            <td class="px-4 py-4 text-slate-500 dark:text-slate-400"><?= esc($system['description']) ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($system['status'])) ?>">
                                    <?= esc(status_label($system['status'])) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="/configuracion/amortizacion/<?= esc($system['guid']) ?>/editar" class="icon-action <?= icon_button_classes('ghost') ?>" title="Editar sistema" aria-label="Editar sistema">
                                        <?= app_icon('edit') ?>
                                    </a>
                                    <form method="post" action="/configuracion/amortizacion/<?= esc($system['guid']) ?>/toggle">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-action <?= icon_button_classes(($system['status'] ?? 'active') === 'active' ? 'rose' : 'emerald') ?>" title="<?= ($system['status'] ?? 'active') === 'active' ? 'Deshabilitar sistema' : 'Habilitar sistema' ?>" aria-label="<?= ($system['status'] ?? 'active') === 'active' ? 'Deshabilitar sistema' : 'Habilitar sistema' ?>">
                                            <?= app_icon(($system['status'] ?? 'active') === 'active' ? 'disable' : 'approve') ?>
                                        </button>
                                    </form>
                                    <form method="post" action="/configuracion/amortizacion/<?= esc($system['guid']) ?>" onsubmit="return confirm('Se eliminara este sistema de amortizacion. Deseas continuar?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="icon-action <?= icon_button_classes('rose') ?>" title="Eliminar sistema" aria-label="Eliminar sistema">
                                            <?= app_icon('delete') ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 p-2 lg:hidden">
            <?php foreach ($systems as $system): ?>
                <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white"><?= esc($system['name']) ?></p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= esc($system['code']) ?></p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($system['status'])) ?>">
                            <?= esc(status_label($system['status'])) ?>
                        </span>
                    </div>
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400"><?= esc($system['description']) ?></p>
                    <div class="mt-4 flex gap-2">
                        <a href="/configuracion/amortizacion/<?= esc($system['guid']) ?>/editar" class="icon-action <?= icon_button_classes('ghost') ?>" title="Editar sistema" aria-label="Editar sistema">
                            <?= app_icon('edit') ?>
                        </a>
                        <form method="post" action="/configuracion/amortizacion/<?= esc($system['guid']) ?>/toggle">
                            <?= csrf_field() ?>
                            <button type="submit" class="icon-action <?= icon_button_classes(($system['status'] ?? 'active') === 'active' ? 'rose' : 'emerald') ?>" title="<?= ($system['status'] ?? 'active') === 'active' ? 'Deshabilitar sistema' : 'Habilitar sistema' ?>" aria-label="<?= ($system['status'] ?? 'active') === 'active' ? 'Deshabilitar sistema' : 'Habilitar sistema' ?>">
                                <?= app_icon(($system['status'] ?? 'active') === 'active' ? 'disable' : 'approve') ?>
                            </button>
                        </form>
                        <form method="post" action="/configuracion/amortizacion/<?= esc($system['guid']) ?>" onsubmit="return confirm('Se eliminara este sistema de amortizacion. Deseas continuar?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="icon-action <?= icon_button_classes('rose') ?>" title="Eliminar sistema" aria-label="Eliminar sistema">
                                <?= app_icon('delete') ?>
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
