<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Usuarios</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">CRUD base para estructura administrativa del sistema.</p>
        </div>
        <a href="/configuracion/usuarios/crear" class="inline-flex items-center gap-2 rounded-2xl border <?= icon_button_classes('dark') ?> px-4 py-3 text-sm font-medium">
            <?= app_icon('add', 'h-4 w-4') ?>
        </a>
    </div>

    <div class="glass-card overflow-hidden p-2">
        <div class="hidden responsive-table lg:block">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/60">
                    <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                        <th class="px-4 py-4">Usuario</th>
                        <th class="px-4 py-4">Email</th>
                        <th class="px-4 py-4">Rol</th>
                        <th class="px-4 py-4">Estado</th>
                        <th class="px-4 py-4">Alta</th>
                        <th class="px-4 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php foreach ($users as $user): ?>
                        <tr class="text-sm">
                            <td class="px-4 py-4 font-medium"><?= esc($user['username']) ?></td>
                            <td class="px-4 py-4"><?= esc($user['email']) ?></td>
                            <td class="px-4 py-4"><?= esc($user['role_label'] ?? 'Operador') ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($user['active'] ? 'active' : 'restricted')) ?>">
                                    <?= $user['active'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td class="px-4 py-4"><?= esc(date('d/m/Y', strtotime($user['created_at']))) ?></td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="/configuracion/usuarios/<?= esc($user['id']) ?>/editar" class="icon-action <?= icon_button_classes('ghost') ?>" title="Editar usuario" aria-label="Editar usuario">
                                        <?= app_icon('edit') ?>
                                    </a>
                                    <?php if (auth()->user()?->can('users.delete')): ?>
                                    <form method="post" action="/configuracion/usuarios/<?= esc($user['id']) ?>/toggle">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-action <?= icon_button_classes($user['active'] ? 'rose' : 'emerald') ?>" title="<?= $user['active'] ? 'Desactivar usuario' : 'Activar usuario' ?>" aria-label="<?= $user['active'] ? 'Desactivar usuario' : 'Activar usuario' ?>">
                                            <?= app_icon($user['active'] ? 'disable' : 'approve') ?>
                                        </button>
                                    </form>
                                    <form method="post" action="/configuracion/usuarios/<?= esc($user['id']) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="icon-action <?= icon_button_classes('rose') ?>" title="Eliminar usuario" aria-label="Eliminar usuario">
                                            <?= app_icon('reject') ?>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 p-2 lg:hidden">
            <?php foreach ($users as $user): ?>
                <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white"><?= esc($user['username']) ?></p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= esc($user['email']) ?></p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= esc($user['role_label'] ?? 'Operador') ?></p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc(status_badge($user['active'] ? 'active' : 'restricted')) ?>">
                            <?= $user['active'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </div>
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Alta <?= esc(date('d/m/Y', strtotime($user['created_at']))) ?></p>
                    <div class="mt-4 flex gap-2">
                        <a href="/configuracion/usuarios/<?= esc($user['id']) ?>/editar" class="icon-action <?= icon_button_classes('ghost') ?>" title="Editar usuario" aria-label="Editar usuario">
                            <?= app_icon('edit') ?>
                        </a>
                        <?php if (auth()->user()?->can('users.delete')): ?>
                        <form method="post" action="/configuracion/usuarios/<?= esc($user['id']) ?>/toggle">
                            <?= csrf_field() ?>
                            <button type="submit" class="icon-action <?= icon_button_classes($user['active'] ? 'rose' : 'emerald') ?>" title="<?= $user['active'] ? 'Desactivar usuario' : 'Activar usuario' ?>" aria-label="<?= $user['active'] ? 'Desactivar usuario' : 'Activar usuario' ?>">
                                <?= app_icon($user['active'] ? 'disable' : 'approve') ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
