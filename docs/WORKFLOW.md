# Workflow del Sistema

## 1. Alta de Cliente (KYC)
1. Usuario interno crea el Customer.
2. Sube y procesa la información (DNI se cifra).
3. Evaluador cambia `kyc_status` a `verified`.

## 2. Solicitud de Préstamo
1. Se asocia un Customer verified a una `LoanApplication`.
2. Se introducen los datos: monto, moneda, plazo, tipo de amortización (francés/alemán/americano).
3. Se evalúa el riesgo y se pasa a `approved`.

## 3. Desembolso
1. La `LoanApplication` aprobada se desembolsa (`disburse`).
2. Esto crea el registro maestro en `loans`.
3. El `AmortizationService` entra en acción, calcula el cronograma mediante BCMath según el método elegido y la tasa de interés.
4. Las cuotas resultantes se persisten en `installments`.
5. El sistema envía notificaciones pertinentes de aprobación/desembolso.

## 4. Pagos y Cobranza
1. Los pagos entran a `payments`, referenciando al `installment_guid`.
2. El `PaymentService` deduce del `remaining_balance` de la cuota.
3. El préstamo ajusta su `outstanding_balance`.
4. Todos los movimientos dentro de este proceso ocurren dentro de transacciones SQL atómicas `$db->transStart()`.
