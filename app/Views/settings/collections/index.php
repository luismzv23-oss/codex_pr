<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Cobros</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Catalogo de cuentas y medios de cobro para transferencias y billeteras.</p>
        </div>
        <a href="/configuracion/cobros/crear" class="inline-flex items-center gap-2 rounded-2xl border <?= icon_button_classes('dark') ?> px-4 py-3 text-sm font-medium">
            <?= app_icon('add', 'h-4 w-4') ?>
        </a>
    </div>

    <div class="glass-card overflow-hidden p-2">
        <div class="hidden responsive-table lg:block">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/60">
                    <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                        <th class="px-4 py-4">Nombre</th>
                        <th class="px-4 py-4">CUIT</th>
                        <th class="px-4 py-4">CBU</th>
                        <th class="px-4 py-4">Alias</th>
                        <th class="px-4 py-4">Entidad</th>
                        <th class="px-4 py-4">Estado</th>
                        <th class="px-4 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php foreach ($methods as $method): ?>
                        <tr class="text-sm">
                            <td class="px-4 py-4 font-medium"><?= esc($method['name']) ?></td>
                            <td class="px-4 py-4"><?= esc($method['cuit']) ?></td>
                            <td class="px-4 py-4"><?= esc($method['cbu']) ?></td>
                            <td class="px-4 py-4"><?= esc($method['account_alias']) ?></td>
                            <td class="px-4 py-4"><?= esc($method['entity']) ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($method['status'] ?? 'active')) ?>">
                                    <?= esc(status_label($method['status'] ?? 'active')) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="/configuracion/cobros/<?= esc($method['guid']) ?>/editar" class="icon-action <?= icon_button_classes('ghost') ?>" title="Editar cobro" aria-label="Editar cobro">
                                        <?= app_icon('edit') ?>
                                    </a>
                                    <form method="post" action="/configuracion/cobros/<?= esc($method['guid']) ?>/toggle">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-action <?= icon_button_classes(($method['status'] ?? 'active') === 'active' ? 'rose' : 'emerald') ?>" title="<?= ($method['status'] ?? 'active') === 'active' ? 'Deshabilitar cobro' : 'Habilitar cobro' ?>" aria-label="<?= ($method['status'] ?? 'active') === 'active' ? 'Deshabilitar cobro' : 'Habilitar cobro' ?>">
                                            <?= app_icon(($method['status'] ?? 'active') === 'active' ? 'disable' : 'approve') ?>
                                        </button>
                                    </form>
                                    <form method="post" action="/configuracion/cobros/<?= esc($method['guid']) ?>" onsubmit="return confirm('Se eliminara esta forma de cobro. Deseas continuar?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="icon-action <?= icon_button_classes('rose') ?>" title="Eliminar cobro" aria-label="Eliminar cobro">
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
            <?php foreach ($methods as $method): ?>
                <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white"><?= esc($method['name']) ?></p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= esc($method['entity']) ?></p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($method['status'] ?? 'active')) ?>">
                            <?= esc(status_label($method['status'] ?? 'active')) ?>
                        </span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <p><strong class="text-slate-900 dark:text-white">CUIT:</strong> <?= esc($method['cuit']) ?></p>
                        <p><strong class="text-slate-900 dark:text-white">CBU:</strong> <?= esc($method['cbu']) ?></p>
                        <p><strong class="text-slate-900 dark:text-white">Alias:</strong> <?= esc($method['account_alias']) ?></p>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="/configuracion/cobros/<?= esc($method['guid']) ?>/editar" class="icon-action <?= icon_button_classes('ghost') ?>" title="Editar cobro" aria-label="Editar cobro">
                            <?= app_icon('edit') ?>
                        </a>
                        <form method="post" action="/configuracion/cobros/<?= esc($method['guid']) ?>/toggle">
                            <?= csrf_field() ?>
                            <button type="submit" class="icon-action <?= icon_button_classes(($method['status'] ?? 'active') === 'active' ? 'rose' : 'emerald') ?>" title="<?= ($method['status'] ?? 'active') === 'active' ? 'Deshabilitar cobro' : 'Habilitar cobro' ?>" aria-label="<?= ($method['status'] ?? 'active') === 'active' ? 'Deshabilitar cobro' : 'Habilitar cobro' ?>">
                                <?= app_icon(($method['status'] ?? 'active') === 'active' ? 'disable' : 'approve') ?>
                            </button>
                        </form>
                        <form method="post" action="/configuracion/cobros/<?= esc($method['guid']) ?>" onsubmit="return confirm('Se eliminara esta forma de cobro. Deseas continuar?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="icon-action <?= icon_button_classes('rose') ?>" title="Eliminar cobro" aria-label="Eliminar cobro">
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
