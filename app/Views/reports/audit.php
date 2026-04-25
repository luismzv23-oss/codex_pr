<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Auditoria</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Eventos recientes de la base de trabajo.</p>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-slate-800 bg-slate-950 shadow-2xl">
        <div class="divide-y divide-slate-800 font-mono text-sm">
            <?php foreach ($logs as $log): ?>
                <div class="px-6 py-4 text-slate-300">
                    <span class="text-emerald-300"><?= esc(date('Y-m-d H:i:s', strtotime($log['created_at']))) ?></span>
                    <span class="mx-2 text-slate-600">|</span>
                    <span class="text-cyan-300"><?= esc($log['action']) ?></span>
                    <span class="mx-2 text-slate-600">|</span>
                    <span><?= esc($log['entity_type']) ?>:<?= esc($log['entity_guid']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
