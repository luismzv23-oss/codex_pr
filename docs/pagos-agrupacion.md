# Pagos agrupados por cliente y préstamo

El listado tiene dos niveles colapsables: cliente y código de préstamo. Dentro de cada préstamo se muestran los pagos con monto, método, referencia y fecha. Ambos niveles comienzan cerrados y se pueden operar con mouse o teclado mediante details/summary.

La paginación cuenta clientes: hasta 6 clientes con pagos coincidentes por página. No divide los préstamos o pagos de un cliente entre páginas. Los clientes se ordenan por su pago coincidente más reciente; préstamos y pagos se muestran por fecha descendente. Los grupos utilizan los identificadores de cliente y préstamo, por lo que clientes con el mismo nombre permanecen separados.

La búsqueda acepta cliente, referencia, ID y código habitual de préstamo. Los filtros de método y moneda se aplican a los pagos antes de agrupar. Los contadores de préstamos y pagos de cada cliente corresponden únicamente a los resultados filtrados.

La navegación y los filtros actualizan únicamente el listado mediante fetch. La página y los filtros quedan en la URL y se recuperan al regresar desde el registro de un pago. El estado de los dos niveles se conserva durante el refresco, al cambiar de página y al regresar al listado en la misma pestaña.

Los datos se consultan al recuperar el foco, al volver a estar visibles y cada 15 segundos mientras la pestaña esté visible. Si disminuyen los resultados, se ajusta la página. Los errores conservan los datos visibles y muestran un mensaje.

## Integración y despliegue

Publicar app/Config/Routes.php, app/Controllers/PaymentController.php, app/Support/AppRepository.php, app/Views/payments/index.php, app/Views/payments/_listing.php y public/assets/js/payments.js. No requiere migraciones ni dependencias nuevas.

GET /pagos/listado requiere sesión y permiso payments.view. Recibe buscar, metodo, moneda y page. Devuelve html y pagination; total y per_page se refieren a clientes, no a pagos. Los códigos de préstamo utilizan la numeración global vigente.

## Pruebas

```text
php vendor/phpunit/phpunit/phpunit --no-coverage tests/unit
php vendor/phpunit/phpunit/phpunit --no-coverage tests/integration/PaymentGroupingTest.php
node --test tests/js/payments.test.cjs
```

Las pruebas de integración escriben solamente en tablas TEMPORARY con prefijo aleatorio en una conexión exclusiva.

En navegador, expandir un cliente y un préstamo, verificar los pagos y esperar un refresco. Ambos niveles deben conservar su estado. Navegar a otra página y volver, filtrar por método y moneda y registrar un pago de prueba. Al regresar, comprobar el contexto, los grupos abiertos y los datos actualizados.
