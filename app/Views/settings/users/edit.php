<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-3xl page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Editar usuario</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Ajuste base del perfil administrativo.</p>
    </div>

    <form method="post" action="/configuracion/usuarios/<?= esc($user['id']) ?>" class="glass-card space-y-6 p-8">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="grid gap-6 md:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-medium">Usuario</span>
                <input name="username" value="<?= old('username', $user['username']) ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Email</span>
                <input type="email" name="email" value="<?= old('email', $user['email']) ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2 md:col-span-2">
                <span class="text-sm font-medium">Nueva password (opcional)</span>
                <input type="password" name="password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
            </label>
            <label class="inline-flex items-center gap-3 text-sm font-medium">
                <input type="checkbox" name="active" value="1" class="h-5 w-5 rounded border-slate-300 text-slate-900" <?= old('active', $user['active'] ? '1' : '') ? 'checked' : '' ?>>
                <span>Usuario activo</span>
            </label>
        </div>

        <div class="flex items-center justify-between">
            <a href="/configuracion/usuarios" class="text-sm text-slate-500 hover:text-slate-800 dark:hover:text-white">Volver</a>
            <button class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-medium text-white dark:bg-white dark:text-slate-950">
                <?= app_icon('save', 'h-4 w-4') ?>
                <span>Actualizar usuario</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
