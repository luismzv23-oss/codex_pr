<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Analisis de mora</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Cuotas vencidas agrupadas por cliente y prestamo.</p>
    </div>

    <form id="overdue-filters" method="get" action="/reportes/mora" data-ajax-filter class="glass-card grid items-end gap-4 p-5 sm:grid-cols-[1fr_auto]">
        <label class="space-y-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Cliente</span>
            <?= view('components/customer_select', [
                'customers' => $customerOptions,
                'lockedCustomerGuid' => $cliente,
                'customerSelectId' => 'overdue-customer',
                'customerSelectName' => 'cliente',
                'customerSelectRequired' => false,
                'customerSelectPlaceholder' => 'Todos los clientes',
            ]) ?>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-900 bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-white dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100">
                <?= app_icon('filter', 'h-4 w-4') ?>
                <span>Filtrar</span>
            </button>
            <a data-clear-filters href="/reportes/mora" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white p-2.5 text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" title="Limpiar filtros" aria-label="Limpiar filtros">
                <?= app_icon('close', 'h-5 w-5') ?>
            </a>
        </div>
    </form>

    <div id="overdue-list" data-ajax-list class="space-y-4" aria-live="polite">
        <?= view('reports/_overdue_listing', ['groups' => $groups, 'pagination' => $pagination, 'cliente' => $cliente]) ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>#overdue-list details[open] > summary .payment-chevron { transform: rotate(90deg); }</style>
<script defer src="<?= esc(base_url('assets/js/customer-select.js'), 'attr') ?>"></script>
<script defer src="<?= esc(base_url('assets/js/ajax-pager.js'), 'attr') ?>"></script>
<?= $this->endSection() ?>
