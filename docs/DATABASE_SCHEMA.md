# Esquema de Base de Datos

Las tablas principales del sistema utilizan `CHAR(36)` (GUID) como clave primaria.

## 1. Customers (`customers`)
Almacena la información de los clientes (prestatarios).
- `guid` (PK)
- `first_name`, `last_name`, `email`, `phone`
- `dni_encrypted` (Cifrado AES-256)
- `kyc_status` (pending, verified, rejected)
- `risk_score`

## 2. Solicitudes (`loan_applications`)
Representa una evaluación crediticia.
- `guid` (PK)
- `customer_guid` (FK)
- `requested_amount`, `approved_amount`, `currency`
- `interest_rate`, `term_months`, `amortization_type`
- `status` (draft, evaluation, approved, rejected, disbursed)

## 3. Préstamos (`loans`)
El préstamo activo después del desembolso.
- `guid` (PK)
- `application_guid`, `customer_guid`
- `principal_amount`, `currency`
- `total_interest`, `total_payable`, `outstanding_balance`
- `status` (active, paid_off, defaulted)

## 4. Cuotas (`installments`)
El cronograma de pagos generado.
- `guid` (PK)
- `loan_guid` (FK)
- `installment_number`, `due_date`
- `principal_amount`, `interest_amount`, `total_amount`, `paid_amount`, `remaining_balance`
- `status` (pending, paid, partial, overdue)

## 5. Pagos (`payments`)
Los flujos de dinero entrantes.
- `guid` (PK)
- `loan_guid`, `installment_guid`, `customer_guid`
- `amount`, `currency`, `payment_method`

## 6. Monedas (`currencies`)
Registro multimoneda (ej: ARS, USD, EUR).
- `guid` (PK)
- `code`, `name`, `symbol`, `exchange_rate`

## 7. Logs de Auditoría (`audit_logs`)
- Registra `user_guid`, `action`, `entity_type`, `entity_guid`, `old_values`, y `new_values`.
