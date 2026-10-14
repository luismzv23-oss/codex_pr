# Paginación de Préstamos generados

Préstamos generados muestra hasta 6 tarjetas por página. Anterior/Siguiente, Activos/Cancelados, búsqueda y filtros actualizan únicamente el listado, sin recargar la plantilla. El bloque de solicitudes pendientes de aprobación conserva su funcionamiento actual.

Activos incluye active, defaulted y restructured. Cancelados incluye paid y paid_off. Cambiar de grupo reinicia la página y el estado detallado, conservando la búsqueda y la moneda. Limpiar filtros conserva el grupo actual.

La búsqueda mantiene el comportamiento por nombre de cliente, ID y alias. Los alias conservan su numeración global: se consulta metadata de los préstamos para calcularlos antes de filtrar y se cargan los datos completos de los seis registros visibles. No se recalculan los scores de todos los clientes para actualizar este listado.

La página, el grupo y los filtros quedan en la URL y en el estado de la pestaña. El listado se actualiza al volver de una modificación, recuperar el foco, volver a estar visible y cada 15 segundos mientras la pestaña esté visible. Si queda fuera de rango, se ajusta a la última página. Los errores de conexión conservan los datos visibles y presentan un mensaje.

## Integración y despliegue

Publicar app/Config/Routes.php, app/Controllers/LoanController.php, app/Support/AppRepository.php, app/Views/loans/index.php, app/Views/loans/_listing.php y public/assets/js/loans.js. No requiere migraciones ni dependencias nuevas.

GET /prestamos/listado requiere sesión y permiso loans.view. Recibe estado (active o cancelled), buscar, estado_detalle, moneda y page. Devuelve html y pagination. El límite de 6 se establece en el servidor.

## Verificación

```text
php vendor/phpunit/phpunit/phpunit --no-coverage tests/unit
php vendor/phpunit/phpunit/phpunit --no-coverage tests/integration/LoanPaginationTest.php
node --test tests/js/loans.test.cjs
```

Las pruebas de integración escriben exclusivamente en tablas TEMPORARY con prefijo aleatorio y conexión independiente. No modifican préstamos reales.

En navegador, avanzar y retroceder, cambiar de grupo, buscar un alias existente, aplicar estado detallado y moneda y limpiar los filtros. Abrir una ficha o registrar un pago y volver: verificar contexto conservado, saldo actualizado y clasificación correspondiente. Comprobar que la plantilla y el bloque de aprobación permanecen visibles al navegar por el listado.
