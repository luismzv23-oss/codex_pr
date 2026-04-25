# Especificaciones de Seguridad

## 1. Cifrado en Reposo
- Los datos de identificación personal (PII) como el "DNI" se cifran antes de guardarse.
- Se utiliza la clase nativa de CI4 `\Config\Services::encrypter()`.
- Algoritmo configurado: `AES-256-CTR`.
- La llave está centralizada en `.env` bajo `encryption.key`.

## 2. Autenticación CodeIgniter Shield
- Shield maneja las contraseñas hasheadas usando `PASSWORD_DEFAULT` (Argon2i/Bcrypt dependiendo del entorno PHP).
- Las sesiones están protegidas por el filtro `session` en todas las rutas bajo `/dashboard`, `/customers`, etc.
- **2FA:** Shield permite autenticación en 2 pasos de forma nativa por email (`Email2FA`).

## 3. Trazabilidad (Audit Logs)
- El `AuditFilter` intercepta peticiones a endpoints críticos de modificación.
- `AuditService` registra el ID de usuario responsable y todos los cambios (deltas `old_values` y `new_values`).

## 4. Protecciones Generales CI4
- **CSRF:** Se configuró globalmente en todos los métodos POST/PUT/DELETE.
- Inyección SQL: Mitigada al utilizar Models/Query Builder predeterminados de CI4.
