<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <?= $this->include('pdf/_styles') ?>
</head>
<body>
    <h1><?= esc($title) ?></h1>
    <p class="muted"><?= esc($loan['alias'] ?? $loan['guid']) ?> · Fecha <?= esc(date('d/m/Y', strtotime($loan['disbursed_at'] ?? 'now'))) ?></p>

    <div class="section">
        <p>Entre la parte acreedora y <strong><?= esc($customer['full_name'] ?? 'Cliente') ?></strong>, identificado en el sistema, se deja constancia del otorgamiento del prestamo detallado a continuacion. El cliente se compromete a cancelar las cuotas en las fechas previstas, incluyendo capital, interes, cargos aplicables y cualquier otro importe pactado segun el cronograma.</p>
    </div>

    <table class="report">
        <tbody>
            <tr><th>Cliente</th><td><?= esc($customer['full_name'] ?? 'Cliente no disponible') ?></td><th>Prestamo</th><td><?= esc($loan['alias'] ?? $loan['guid']) ?></td></tr>
            <tr><th>Monto aprobado</th><td><?= esc(money($loan['principal_amount'], $loan['currency'])) ?></td><th>Tasa</th><td><?= esc(interest_percent($loan['interest_rate'])) ?>%</td></tr>
            <tr><th>Plazo</th><td><?= esc($loan['term_months']) ?> meses</td><th>Sistema</th><td><?= esc(amortization_system_label($loan['amortization_type'])) ?></td></tr>
            <tr><th>Total estimado</th><td><?= esc(money($loan['total_payable'], $loan['currency'])) ?></td><th>Solicitud</th><td><?= esc($application['guid'] ?? 'N/D') ?></td></tr>
        </tbody>
    </table>

    <h2>Cronograma pactado</h2>
    <table class="report">
        <thead>
            <tr><th>Cuota</th><th>Vencimiento</th><th>Total</th></tr>
        </thead>
        <tbody>
            <?php foreach ($installments as $item): ?>
                <tr>
                    <td><?= esc($item['installment_number']) ?></td>
                    <td><?= esc(date('d/m/Y', strtotime($item['due_date']))) ?></td>
                    <td><?= esc(money($item['total_amount'], $loan['currency'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Cuentas habilitadas para transferencias</h2>
    <table class="report">
        <thead>
            <tr><th>Nombre</th><th>Entidad</th><th>CBU</th><th>Alias</th></tr>
        </thead>
        <tbody>
            <?php foreach ($collectionMethods as $method): ?>
                <tr>
                    <td><?= esc($method['name']) ?></td>
                    <td><?= esc($method['entity']) ?></td>
                    <td><?= esc($method['cbu']) ?></td>
                    <td><?= esc($method['account_alias']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($collectionMethods === []): ?>
                <tr><td colspan="4">No hay cuentas activas configuradas al momento de la emision.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td>
                <div class="line"></div>
                <p>Firma del cliente</p>
            </td>
            <td>
                <div class="line"></div>
                <p>Firma y sello de la entidad</p>
            </td>
        </tr>
    </table>
</body>
</html>
