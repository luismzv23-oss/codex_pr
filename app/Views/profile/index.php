<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-3xl page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Mi perfil</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?= esc($user->username ?? 'Usuario') ?> - <?= esc($user->getEmail() ?? '') ?></p>
    </div>

    <form method="post" action="/perfil/password" class="glass-card space-y-6 p-8">
        <?= csrf_field() ?>
        <div class="grid gap-6">
            <label class="space-y-2">
                <span class="text-sm font-medium">Contrasena actual</span>
                <input type="password" name="current_password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Nueva contrasena</span>
                <input type="password" name="password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Confirmar nueva contrasena</span>
                <input type="password" name="password_confirm" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
            </label>
        </div>

        <div class="flex justify-end">
            <button class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-medium text-white dark:bg-white dark:text-slate-950">
                <?= app_icon('save', 'h-4 w-4') ?>
                <span>Actualizar contrasena</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
