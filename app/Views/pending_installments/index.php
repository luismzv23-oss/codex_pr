<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Cuotas pendientes</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Proxima cuota pendiente del mes en curso por prestamo.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="glass-card p-5">
            <?php
            $periodoLabel = 'Cuotas del mes';
            if ($periodo === 'overdue') {
                $periodoLabel = 'Cuotas vencidas (Mora)';
            } elseif ($periodo === 'all') {
                $periodoLabel = 'Todas las cuotas pendientes';
            }
            ?>
            <p class="text-sm text-slate-500 dark:text-slate-400"><?= esc($periodoLabel) ?></p>
            <p class="mt-3 text-3xl font-semibold"><?= esc($summary['total']) ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Saldo pendiente</p>
            <p class="mt-3 text-3xl font-semibold"><?= esc(money($summary['amount_due'])) ?></p>
        </div>
    </div>

    <form method="get" action="/cuotas-pendientes" class="glass-card grid gap-4 p-5 sm:grid-cols-2 md:grid-cols-4 items-end">
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Buscar</span>
            <input type="text" name="buscar" value="<?= esc($buscar) ?>" placeholder="Cliente o préstamo..." class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vencimiento</span>
            <select name="periodo" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="current_month" <?= $periodo === 'current_month' ? 'selected' : '' ?>>Este mes</option>
                <option value="overdue" <?= $periodo === 'overdue' ? 'selected' : '' ?>>Todos los vencidos (Mora)</option>
                <option value="all" <?= $periodo === 'all' ? 'selected' : '' ?>>Todos los pendientes</option>
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</span>
            <select name="estado" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="pending" <?= $estado === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                <option value="partial" <?= $estado === 'partial' ? 'selected' : '' ?>>Parcial</option>
                <option value="overdue" <?= $estado === 'overdue' ? 'selected' : '' ?>>En mora</option>
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950 px-4 py-2.5 text-sm font-medium transition hover:bg-slate-800 dark:hover:bg-slate-100 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <?= app_icon('filter', 'h-4 w-4') ?>
                <span>Filtrar</span>
            </button>
            <?php if ($buscar !== '' || $estado !== '' || $periodo !== 'current_month'): ?>
                <a href="/cuotas-pendientes" class="inline-flex items-center justify-center p-2.5 rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" title="Limpiar filtros">
                    <?= app_icon('close', 'h-5 w-5') ?>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <div id="pending-list" data-ajax-list class="space-y-4" aria-live="polite">
        <?= view('pending_installments/_listing', ['installments' => $installments, 'pagination' => $pagination, 'buscar' => $buscar, 'estado' => $estado, 'periodo' => $periodo]) ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script defer src="<?= esc(base_url('assets/js/ajax-pager.js'), 'attr') ?>"></script>
<?= $this->endSection() ?>
