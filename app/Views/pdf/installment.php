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
        <table class="grid">
            <tr>
                <td><strong>Cliente:</strong> <?= esc($customer['full_name'] ?? 'Cliente no disponible') ?></td>
                <td><strong>Prestamo:</strong> <?= esc($loan['alias'] ?? $loan['guid']) ?></td>
            </tr>
            <tr>
                <td><strong>Cuota:</strong> <?= esc($installment['installment_number']) ?></td>
                <td><strong>Vencimiento:</strong> <?= esc(date('d/m/Y', strtotime($installment['due_date']))) ?></td>
            </tr>
        </table>
    </div>

    <table class="report">
        <thead>
            <tr>
                <th>Capital</th>
                <th>Interes</th>
                <th>Total</th>
                <th>Pagado</th>
                <th>Saldo pendiente</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= esc(money($installment['principal_amount'], $installment['currency'])) ?></td>
                <td><?= esc(money($installment['interest_amount'], $installment['currency'])) ?></td>
                <td><?= esc(money($installment['total_amount'], $installment['currency'])) ?></td>
                <td><?= esc(money($installment['paid_amount'], $installment['currency'])) ?></td>
                <td><?= esc(money($installment['amount_due'], $installment['currency'])) ?></td>
                <td><?= esc(status_label($installment['status'])) ?></td>
            </tr>
        </tbody>
    </table>

    <h2>Cuentas para transferir</h2>
    <table class="report">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Entidad</th>
                <th>CUIT</th>
                <th>CBU</th>
                <th>Alias</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($collectionMethods as $method): ?>
                <tr>
                    <td><?= esc($method['name']) ?></td>
                    <td><?= esc($method['entity']) ?></td>
                    <td><?= esc($method['cuit']) ?></td>
                    <td><?= esc($method['cbu']) ?></td>
                    <td><?= esc($method['account_alias']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($collectionMethods === []): ?>
                <tr><td colspan="5">No hay cuentas activas configuradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="barcode"><?= code39_svg((string) $barcodeValue) ?></div>
    <p class="footer-note">Presenta este comprobante al momento de informar la transferencia o pago de la cuota.</p>
</body>
</html>
