<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <?= $this->include('pdf/_styles') ?>
</head>
<body>
    <h1><?= esc($title) ?></h1>
    <p class="muted"><?= esc($statement['customer']['full_name']) ?> · <?= esc($statement['loan']['alias'] ?? $statement['loan']['guid']) ?></p>

    <table class="report">
        <tbody>
            <tr><th>Cuotas totales</th><td><?= esc($statement['summary']['total_installments']) ?></td><th>Cuotas pagadas</th><td><?= esc($statement['summary']['paid_installments']) ?></td></tr>
            <tr><th>Total abonado</th><td><?= esc(money($statement['summary']['total_paid'], $statement['loan']['currency'])) ?></td><th>Total pendiente</th><td><?= esc(money($statement['summary']['total_pending'], $statement['loan']['currency'])) ?></td></tr>
        </tbody>
    </table>

    <h2>Cuotas agrupadas por prestamo</h2>
    <?php foreach ($statement['installments_grouped'] as $group): ?>
        <h3><?= esc($group['loan']['alias'] ?? $group['loan']['guid'] ?? 'Prestamo') ?></h3>
        <table class="report">
            <thead>
                <tr><th>Cuota</th><th>Vence</th><th>Total</th><th>Pagado</th><th>Estado</th></tr>
            </thead>
            <tbody>
                <?php foreach ($group['items'] as $item): ?>
                    <tr>
                        <td><?= esc($item['installment_number']) ?></td>
                        <td><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></td>
                        <td><?= esc(money($item['total_amount'], $item['currency'])) ?></td>
                        <td><?= esc(money($item['paid_amount'], $item['currency'])) ?></td>
                        <td><?= esc(status_label($item['status'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <h2>Historial de pagos</h2>
    <table class="report">
        <thead>
            <tr><th>Prestamo</th><th>Fecha</th><th>Monto</th><th>Metodo</th><th>Referencia</th></tr>
        </thead>
        <tbody>
            <?php foreach ($statement['payments'] as $payment): ?>
                <tr>
                    <td><?= esc($payment['loan_label']) ?></td>
                    <td><?= esc(date('d/m/Y H:i', strtotime($payment['created_at']))) ?></td>
                    <td><?= esc(money($payment['amount'], $payment['currency'])) ?></td>
                    <td><?= esc(ucfirst($payment['payment_method'])) ?></td>
                    <td><?= esc($payment['reference_number'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($statement['payments'] === []): ?>
                <tr><td colspan="5">Todavia no hay pagos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
