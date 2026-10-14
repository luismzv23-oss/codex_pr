    <div class="grid gap-4 md:grid-cols-3">
        <?php foreach (['draft', 'evaluation', 'approved'] as $status): ?>
            <div class="glass-card p-5">
                <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc(status_label($status)) ?></p>
                <p class="mt-3 text-3xl font-semibold">
                    <?= (int) $summary[$status] ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>

