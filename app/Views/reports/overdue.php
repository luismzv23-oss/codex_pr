<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Analisis de mora</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Cuotas vencidas detectadas en la base actual.</p>
    </div>

    <div id="overdue-list" data-ajax-list class="space-y-4" aria-live="polite">
        <?= view('reports/_overdue_listing', ['installments' => $installments, 'pagination' => $pagination]) ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script defer src="<?= esc(base_url('assets/js/ajax-pager.js'), 'attr') ?>"></script>
<?= $this->endSection() ?>
