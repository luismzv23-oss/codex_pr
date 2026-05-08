<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-5xl page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Exportar / Importar datos</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Gestiona las tablas del sistema en formato CSV. La tabla de usuarios no esta disponible por seguridad.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <!-- EXPORT -->
        <div class="glass-card p-6 space-y-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <?= app_icon('pdf', 'h-5 w-5') ?>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Exportar CSV</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Descarga los datos de una tabla en formato CSV.</p>
                </div>
            </div>

            <form method="get" action="/datos/exportar" class="space-y-4">
                <label class="space-y-2">
                    <span class="text-sm font-medium">Tabla</span>
                    <select name="table" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                        <option value="">Seleccionar tabla</option>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?= esc($table) ?>"><?= esc($table) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="flex justify-end">
                    <button class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white hover:bg-emerald-500">
                        <?= app_icon('pdf', 'h-4 w-4') ?>
                        <span>Exportar CSV</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- IMPORT -->
        <div class="glass-card p-6 space-y-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400">
                    <?= app_icon('save', 'h-5 w-5') ?>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Importar CSV</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Sube un archivo CSV para cargar datos en una tabla.</p>
                </div>
            </div>

            <form method="post" action="/datos/importar" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>
                <label class="space-y-2">
                    <span class="text-sm font-medium">Tabla destino</span>
                    <select name="table" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900" required>
                        <option value="">Seleccionar tabla</option>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?= esc($table) ?>"><?= esc($table) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium">Archivo CSV</span>
                    <input type="file" name="csv_file" accept=".csv"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:file:bg-slate-800 dark:file:text-slate-200"
                        required>
                </label>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
                    El CSV debe usar <strong>punto y coma (;)</strong> como separador. La primera fila debe contener los nombres de las columnas. Los registros existentes se actualizan si coinciden las claves primarias.
                </div>

                <div class="flex justify-end">
                    <button class="inline-flex items-center gap-2 rounded-2xl bg-sky-600 px-5 py-3 text-sm font-medium text-white hover:bg-sky-500">
                        <?= app_icon('save', 'h-4 w-4') ?>
                        <span>Importar CSV</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
