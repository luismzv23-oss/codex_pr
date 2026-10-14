# Listado de Clientes

El listado muestra 5 clientes por página. Los filtros y los controles Anterior/Siguiente actualizan únicamente el listado. La columna Total de préstamos cuenta registros de loans vinculados al cliente, incluidos los finalizados y excluidos los eliminados.

La página y los filtros quedan en la URL y se conservan en la pestaña al volver desde una ficha o formulario. El listado se actualiza al recuperar el foco o volver a estar visible, y cada 15 segundos mientras esté visible. Los errores mantienen los datos mostrados y presentan un mensaje.

## Integración y despliegue

Publicar los cambios de app/Config/Routes.php, app/Controllers/CustomerController.php, app/Support/AppRepository.php, app/Views/customers/index.php, app/Views/customers/_listing.php y public/assets/js/customers.js. No requiere migraciones ni dependencias nuevas. El endpoint GET /clientes/listado usa sesión y permiso customers.view.

## Pruebas

- Ejecutar node --test tests/js/customers.test.cjs.
- Ejecutar php vendor/phpunit/phpunit/phpunit --no-coverage tests/unit.
- Abrir Clientes, navegar entre páginas y aplicar filtros: cada página contiene hasta 5 clientes y la plantilla permanece visible.
- Editar un cliente y volver a Clientes: se conserva el contexto y se muestran los datos guardados.
- Modificar un cliente o crear un préstamo desde otra pestaña: recuperar el foco o esperar hasta 15 segundos para ver el cambio.
- Comprobar un cliente sin préstamos (0) y otro con préstamos finalizados.

Los formularios existentes mantienen su navegación y guardado actuales; la navegación sin recarga corresponde al listado y sus filtros.
