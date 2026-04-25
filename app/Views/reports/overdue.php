<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-transition space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">Analisis de mora</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Cuotas vencidas detectadas en la base actual.</p>
    </div>

    <div class="glass-card overflow-hidden p-2">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-900/60">
                <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                    <th class="px-4 py-4">Cuota</th>
                    <th class="px-4 py-4">Prestamo</th>
                    <th class="px-4 py-4">Vencimiento</th>
                    <th class="px-4 py-4">Monto</th>
                    <th class="px-4 py-4">Late fee</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                <?php foreach ($installments as $item): ?>
                    <tr class="text-sm">
                        <td class="px-4 py-4"><?= esc($item['installment_number']) ?></td>
                        <td class="px-4 py-4"><?= esc($item['loan_guid']) ?></td>
                        <td class="px-4 py-4"><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></td>
                        <td class="px-4 py-4"><?= esc(money($item['total_amount'])) ?></td>
                        <td class="px-4 py-4"><?= esc(money($item['late_fee'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
