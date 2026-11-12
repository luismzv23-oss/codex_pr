<div class="overflow-hidden rounded-[1.75rem] border border-slate-800 bg-slate-950 shadow-2xl">
    <div class="divide-y divide-slate-800 font-mono text-sm">
        <?php if ($logs === []): ?>
            <div class="px-6 py-4 text-slate-400">Sin eventos registrados.</div>
        <?php endif; ?>
        <?php foreach ($logs as $log): ?>
            <div class="break-all px-6 py-4 text-slate-300">
                <span class="text-emerald-300"><?= esc(date('Y-m-d H:i:s', strtotime($log['created_at']))) ?></span>
                <span class="mx-2 text-slate-600">|</span>
                <span class="text-cyan-300"><?= esc($log['action']) ?></span>
                <span class="mx-2 text-slate-600">|</span>
                <span><?= esc($log['entity_type']) ?>:<?= esc($log['entity_guid']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?= view('components/pager', ['pagination' => $pagination, 'baseUrl' => '/reportes/auditoria', 'noun' => 'eventos', 'label' => 'Paginación de auditoría']) ?>
