<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Auditoria</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Eventos recientes de la base de trabajo.</p>
    </div>

    <div id="audit-list" data-ajax-list class="space-y-4" aria-live="polite">
        <?= view('reports/_audit_listing', ['logs' => $logs, 'pagination' => $pagination]) ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script defer src="<?= esc(base_url('assets/js/ajax-pager.js'), 'attr') ?>"></script>
<?= $this->endSection() ?>
