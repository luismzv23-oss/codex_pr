<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <?= $this->include('pdf/_styles') ?>
</head>
<body>
    <h1><?= esc($title) ?></h1>
    <p class="muted"><?= esc($loan['alias'] ?? $loan['guid']) ?> · <?= esc($customer['full_name'] ?? 'Cliente') ?></p>

    <table class="report">
        <thead>
            <tr><th>Nro</th><th>Vence</th><th>Capital</th><th>Interes</th><th>Total</th><th>Saldo</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php foreach ($installments as $item): ?>
                <tr>
                    <td><?= esc($item['installment_number']) ?></td>
                    <td><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></td>
                    <td><?= esc(money($item['principal_amount'], $loan['currency'])) ?></td>
                    <td><?= esc(money($item['interest_amount'], $loan['currency'])) ?></td>
                    <td><?= esc(money($item['total_amount'], $loan['currency'])) ?></td>
                    <td><?= esc(money($item['remaining_balance'], $loan['currency'])) ?></td>
                    <td><?= esc(status_label($item['status'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
