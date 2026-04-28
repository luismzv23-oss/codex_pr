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

    <div class="section card">
        <p>Se certifica que el cliente <strong><?= esc($customer['full_name'] ?? 'Cliente no disponible') ?></strong> no registra deuda exigible correspondiente al prestamo <strong><?= esc($loan['alias'] ?? $loan['guid']) ?></strong>, por haberse cancelado totalmente las obligaciones asumidas.</p>
    </div>

    <table class="report">
        <tbody>
            <tr><th>Monto original</th><td><?= esc(money($loan['principal_amount'], $loan['currency'])) ?></td><th>Total cancelado</th><td><?= esc(money($loan['total_payable'], $loan['currency'])) ?></td></tr>
            <tr><th>Estado</th><td><?= esc(status_label($loan['status'])) ?></td><th>Saldo actual</th><td><?= esc(money($loan['outstanding_balance'], $loan['currency'])) ?></td></tr>
        </tbody>
    </table>

    <h2>Pagos aplicados</h2>
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
        </tbody>
    </table>

    <div class="signature">
        <div class="line"></div>
        <p>Firma y sello de la entidad</p>
    </div>
</body>
</html>
