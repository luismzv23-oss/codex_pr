<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Pagos</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Registro base de cobranzas y referencias operativas.</p>
        </div>
        <a href="/pagos/crear" class="icon-action <?= icon_button_classes('accent') ?>" title="Registrar pago" aria-label="Registrar pago">
            <?= app_icon('cash') ?>
        </a>
    </div>
    <form id="payments-filters" method="get" action="/pagos" class="glass-card grid gap-4 p-5 sm:grid-cols-2 md:grid-cols-4 items-end">
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Buscar</span>
            <input type="text" name="buscar" value="<?= esc($buscar) ?>" placeholder="Cliente, referencia, ID o código de préstamo..." class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Método de Pago</span>
            <select name="metodo" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="cash" <?= $metodo === 'cash' ? 'selected' : '' ?>>Efectivo</option>
                <option value="transfer" <?= $metodo === 'transfer' ? 'selected' : '' ?>>Transferencia</option>
                <option value="card" <?= $metodo === 'card' ? 'selected' : '' ?>>Tarjeta</option>
                <option value="check" <?= $metodo === 'check' ? 'selected' : '' ?>>Cheque</option>
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Moneda</span>
            <select name="moneda" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="">Todas</option>
                <option value="ARS" <?= $moneda === 'ARS' ? 'selected' : '' ?>>ARS</option>
                <option value="USD" <?= $moneda === 'USD' ? 'selected' : '' ?>>USD</option>
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950 px-4 py-2.5 text-sm font-medium transition hover:bg-slate-800 dark:hover:bg-slate-100 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <?= app_icon('filter', 'h-4 w-4') ?>
                <span>Filtrar</span>
            </button>

                <a data-clear-filters href="/pagos" class="inline-flex items-center justify-center p-2.5 rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" title="Limpiar filtros">
                    <?= app_icon('close', 'h-5 w-5') ?>
                </a>

        </div>
    </form>

    <p id="payments-error" role="alert" class="hidden text-sm text-red-600 dark:text-red-400"></p>
    <div id="payments-list" class="space-y-4" data-page="<?= (int) $pagination['page'] ?>" data-endpoint="<?= esc(site_url('pagos/listado'), 'attr') ?>">
        <?= $this->include('payments/_listing') ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
    #payments-list details[open] > summary .payment-chevron { transform: rotate(90deg); }
    #payments-list summary::-webkit-details-marker { display: none; }
</style>
<script defer src="<?= esc(base_url('assets/js/payments.js'), 'attr') ?>"></script>
<?= $this->endSection() ?>
