# Paginación de Solicitudes

Solicitudes muestra hasta 6 tarjetas por página. La navegación y los filtros de nombre de cliente, estado, moneda y sistema actualizan el listado mediante fetch sin recargar la plantilla.

Cada tarjeta muestra Total de préstamos del cliente: cantidad de préstamos creados para ese cliente, incluidos los finalizados y excluidos los eliminados. No cuenta solicitudes pendientes o rechazadas como préstamos.

Los contadores superiores corresponden a todos los resultados filtrados, independientemente de la página visible. El listado conserva la exclusión de solicitudes pagadas o vinculadas a préstamos totalmente cancelados. Las solicitudes y préstamos eliminados de forma lógica quedan fuera de las consultas respectivas.

La página y los filtros se guardan en la URL y en la pestaña para recuperarlos al volver desde una ficha o formulario. El listado y los contadores se actualizan al recuperar el foco, volver a estar visibles y cada 15 segundos mientras la pestaña esté visible. Si la página queda fuera de rango, se muestra la última disponible. Un fallo de consulta conserva el listado visible y muestra un mensaje.

## Integración y despliegue

Publicar app/Config/Routes.php, app/Controllers/LoanApplicationController.php, app/Support/AppRepository.php, app/Views/loan_applications/index.php, los fragmentos _listing.php y _summary.php de esa carpeta, y public/assets/js/applications.js. No requiere migraciones ni dependencias nuevas.

GET /solicitudes/listado requiere sesión y permiso applications.view. Recibe buscar, estado, moneda, sistema y page. Devuelve html, summary_html y pagination. El límite de 6 se establece en el servidor.

## Verificación

```text
php vendor/phpunit/phpunit/phpunit --no-coverage tests/unit
php vendor/phpunit/phpunit/phpunit --no-coverage tests/integration/ApplicationPaginationTest.php
node --test tests/js/applications.test.cjs
```

La integración utiliza exclusivamente tablas TEMPORARY con un prefijo aleatorio en una conexión MySQL propia. Los datos reales no se modifican.

En navegador, abrir Solicitudes y avanzar de página: la plantilla debe permanecer visible. Aplicar y limpiar filtros, verificar los contadores superiores, abrir una ficha, modificar su estado y regresar. Comprobar que se recupera el contexto, se actualizan los datos y las tarjetas muestran el total de préstamos del cliente.
