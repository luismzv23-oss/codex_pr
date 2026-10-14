# Fecha del primer cobro

Admin y Superadmin pueden activar Definir fecha del primer cobro al crear una solicitud. El permiso applications.schedule se comprueba también en el servidor. La fecha debe ser válida y no estar en el pasado. La validación usa el día calendario de America/Buenos_Aires.

La primera cuota vence exactamente en la fecha elegida. Las siguientes cuotas vencen mensualmente en el mismo día, usando el último día de un mes cuando el día original no existe y recuperando el original al mes siguiente. Por ejemplo, 31/01/2027, 28/02/2027 y 31/03/2027. La fecha de generación sigue siendo el primer día del mes de vencimiento. Los importes, tasas y sistemas de amortización conservan sus cálculos actuales.

Sin marcar la opción, se conserva el vencimiento automático el día 5 y la regla de inicio según el día de aprobación.

La fecha queda en first_collection_date de loan_applications y loans. Antes de aprobar, si quedó en el pasado, Admin o Superadmin debe corregirla en el formulario de aprobación. La aprobación guarda la corrección, genera las cuotas y actualiza next_due_date. La regeneración y sincronización utilizan el valor persistido, incluso después de que esa fecha haya pasado. Los contratos y cronogramas muestran las fechas de las cuotas.

## Migración y despliegue

La migración 2026-10-14-000001_AddFirstCollectionDate agrega dos campos DATE opcionales con valor predeterminado NULL. Fue aplicada al entorno local. No cambia fechas ni importes de préstamos existentes.

Publicar los cambios de modelos, AuthGroups, controlador, servicios, vistas de solicitud/préstamo y public/assets/js/first-collection-date.js. En otros entornos, ejecutar php spark migrate para incorporar los campos antes de usar el formulario nuevo.

## Verificación

```text
php vendor/phpunit/phpunit/phpunit --no-coverage tests/unit
php vendor/phpunit/phpunit/phpunit --no-coverage tests/integration/FirstCollectionApprovalTest.php
node --test tests/js/first-collection-date.test.cjs
```

Las pruebas de aprobación trabajan exclusivamente sobre tablas TEMPORARY de una conexión de prueba y simulan el guardado del contrato. No crean préstamos ni contratos reales.

Para validar en navegador, crear una solicitud de prueba con fecha futura, comprobar recuperación del checkbox ante errores y aprobarla. Revisar la primera cuota, cuotas siguientes, próximo vencimiento y contrato. Verificar también el formulario sin marcar la opción y que un Operador no pueda establecer la fecha.
