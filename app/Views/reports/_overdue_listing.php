<div class="glass-card overflow-x-auto p-2">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-slate-50 dark:bg-slate-900/60">
            <tr class="text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                <th class="px-4 py-4">Cuota</th>
                <th class="px-4 py-4">Prestamo</th>
                <th class="px-4 py-4">Generacion</th>
                <th class="px-4 py-4">Vencimiento</th>
                <th class="px-4 py-4">Monto</th>
                <th class="px-4 py-4">Late fee</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <?php if ($installments === []): ?>
                <tr class="text-sm"><td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay cuotas vencidas.</td></tr>
            <?php endif; ?>
            <?php foreach ($installments as $item): ?>
                <tr class="text-sm">
                    <td class="px-4 py-4"><?= esc($item['installment_number']) ?></td>
                    <td class="px-4 py-4"><?= esc($item['loan_guid']) ?></td>
                    <td class="px-4 py-4"><?= esc(date('d/m/Y', strtotime($item['generation_date'] ?? date('Y-m-01', strtotime($item['due_date']))))) ?></td>
                    <td class="px-4 py-4"><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></td>
                    <td class="px-4 py-4"><?= esc(money($item['total_amount'])) ?></td>
                    <td class="px-4 py-4"><?= esc(money($item['late_fee'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= view('components/pager', ['pagination' => $pagination, 'baseUrl' => '/reportes/mora', 'noun' => 'cuotas vencidas', 'label' => 'Paginación de cuotas vencidas']) ?>
