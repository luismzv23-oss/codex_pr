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
        <tbody>
            <tr><th>Cliente</th><td><?= esc($customer['full_name'] ?? 'Cliente no disponible') ?></td><th>Moneda</th><td><?= esc($loan['currency']) ?></td></tr>
            <tr><th>Monto</th><td><?= esc(money($loan['principal_amount'], $loan['currency'])) ?></td><th>Tasa</th><td><?= esc(interest_percent($loan['interest_rate'])) ?>%</td></tr>
            <tr><th>Plazo</th><td><?= esc($loan['term_months']) ?> meses</td><th>Sistema</th><td><?= esc(amortization_system_label($loan['amortization_type'])) ?></td></tr>
            <tr><th>Total a cobrar</th><td><?= esc(money($loan['total_payable'], $loan['currency'])) ?></td><th>Saldo pendiente</th><td><?= esc(money($loan['outstanding_balance'], $loan['currency'])) ?></td></tr>
            <tr><th>Estado</th><td><?= esc(status_label($loan['status'])) ?></td><th>Proximo vencimiento</th><td><?= esc(! empty($loan['next_due_date']) ? date('d/m/Y', strtotime($loan['next_due_date'])) : 'Sin fecha') ?></td></tr>
        </tbody>
    </table>

    <h2>Cuotas</h2>
    <table class="report">
        <thead>
            <tr><th>Nro</th><th>Vence</th><th>Total</th><th>Pagado</th><th>Pendiente</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php foreach ($installments as $item): ?>
                <tr>
                    <td><?= esc($item['installment_number']) ?></td>
                    <td><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></td>
                    <td><?= esc(money($item['total_amount'], $loan['currency'])) ?></td>
                    <td><?= esc(money($item['paid_amount'], $loan['currency'])) ?></td>
                    <td><?= esc(money($item['amount_due'] ?? 0, $loan['currency'])) ?></td>
                    <td><?= esc(status_label($item['status'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Pagos registrados</h2>
    <table class="report">
        <thead>
            <tr><th>Fecha</th><th>Monto</th><th>Metodo</th><th>Referencia</th></tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= esc(date('d/m/Y H:i', strtotime($payment['created_at']))) ?></td>
                    <td><?= esc(money($payment['amount'], $payment['currency'])) ?></td>
                    <td><?= esc(ucfirst($payment['payment_method'])) ?></td>
                    <td><?= esc($payment['reference_number'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($payments === []): ?>
                <tr><td colspan="4">Todavia no hay pagos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
