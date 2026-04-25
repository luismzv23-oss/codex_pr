# Arquitectura del Sistema de Préstamos Prestamos

## Tecnologías Principales
- **Framework**: CodeIgniter 4 (v4.3.8 compatible con PHP 8.0)
- **Base de Datos**: MySQL 8+ / MariaDB / PostgreSQL (soporte multimoneda)
- **Autenticación**: CodeIgniter Shield (con 2FA opcional vía Email)
- **Frontend**: Tailwind CSS v3, Alpine.js, ApexCharts (Glassmorphism & Dark Mode)
- **Manejo de UUIDs**: michalsn/codeigniter4-uuid (Generación de GUID v7)

## Capas de la Aplicación

### 1. Controllers (Capa de Presentación)
Controlan las peticiones HTTP y orquestan a los Services y Models. Todas las rutas críticas están protegidas por el filtro `session` de Shield y el filtro personalizado `audit` para la trazabilidad.

### 2. Services (Motor de Negocio)
La lógica pesada está aislada en servicios para facilitar pruebas y reusabilidad:
- **AmortizationService**: Cálculo de cuotas (Sistemas Francés, Alemán, Americano). Utiliza BCMath para asegurar precisión financiera.
- **PaymentService**: Manejo de transacciones, compensación de cuotas (Installments) vs Saldo del préstamo (Loans).
- **EncryptionService**: Usa `AES-256-CTR` para cifrado en reposo del DNI u otros datos PII.
- **AuditService**: Registro inmutable de acciones.
- **LoanWorkflowService**: Administra los estados de una solicitud (draft, evaluation, approved, disbursed, rejected).

### 3. Models y Entities (Capa de Datos)
Todos los modelos extienden de `CodeIgniter\Model` e implementan el trait `HasUuid`. Los IDs primarios (GUIDs) nunca son auto-incrementales.
Las entidades (Entities) encapsulan la lógica inherente a los datos (ej: `$loan->isOverdue()`).

## Flujo de Seguridad Principal
1. Cifrado: Datos sensibles en base de datos almacenados vía EncryptionService.
2. 2FA Opcional: Manejado por CodeIgniter Shield en la fase de login.
3. Auditoría: Registros obligatorios en cualquier alteración de transacciones financieras.
