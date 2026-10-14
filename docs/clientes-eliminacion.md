# Eliminación física de clientes y usuarios

Admin dispone de customers.delete y users.delete. Los botones de eliminación de clientes aparecen en el listado (escritorio y móvil) y en la ficha. El usuario debe revisar los totales asociados y confirmar con su propia contraseña de administrador.

## Alcance

CustomerDeletionService elimina físicamente el cliente, sus solicitudes, préstamos (incluidos los pagados), cuotas, pagos, notificaciones y auditorías vinculadas. Incluye registros eliminados previamente de forma lógica. Usa una transacción SQL y bloqueos de lectura en MySQL para completar toda la cascada o revertir los cambios ante un fallo. No modifica la regla vigente de eliminación individual de préstamos pagados.

Los contratos de {writable}/uploads/contracts/ se eliminan después del commit. Si falla la eliminación de un archivo, la interfaz informa cuántos quedaron pendientes y el log del servidor identifica su ruta. La limpieza pendiente debe realizarse sobre esas rutas concretas después de resolver los permisos del servidor; la operación SQL ya quedó confirmada.

El listado conserva los filtros, se actualiza sin recargar la plantilla y se ajusta a la última página disponible cuando corresponde. Desde la ficha se vuelve al listado.

El borrado de cuentas de acceso ya era físico. Se agregó confirmación y se completó el botón móvil. Eliminar una cuenta de acceso y eliminar un cliente son operaciones independientes.

## API

- GET /clientes/{guid}/eliminacion: obtiene el nombre, los totales asociados y el token CSRF actualizado.
- DELETE /clientes/{guid}: exige admin_password y el token CSRF. La interfaz envía POST con _method=DELETE para usar el parseo de formularios de CodeIgniter.
- Ambas rutas requieren sesión y permiso customers.delete. El servicio vuelve a comprobar el permiso; para eliminar también valida la contraseña.
- Las respuestas incluyen un token CSRF actualizado, incluso ante contraseña incorrecta, para permitir reintentos. Los errores no exponen mensajes SQL.

## Despliegue y pruebas

Publicar el servicio CustomerDeletionService, CustomerController, Routes, las vistas de Clientes y Usuarios, y ambos archivos JavaScript de Clientes. No requiere migraciones ni dependencias nuevas. Las tablas implicadas deben usar InnoDB para garantizar la transacción.

Ejecutar por separado:

```text
php vendor/phpunit/phpunit/phpunit --no-coverage tests/unit
php vendor/phpunit/phpunit/phpunit --no-coverage tests/integration/CustomerDeletionServiceTest.php
node --test tests/js/customers.test.cjs tests/js/customer-deletion.test.cjs
```

La integración usa la conexión MySQL configurada únicamente para crear tablas TEMPORARY con un prefijo aleatorio en una conexión exclusiva y archivos en un directorio temporal. Nunca inserta ni elimina registros de las tablas reales. Las tablas temporales desaparecen al cerrar esa conexión.

Para la comprobación manual, usar un cliente de prueba, cancelar primero el diálogo y después verificar la eliminación con contraseña incorrecta y correcta. Comprobar el refresco del listado, el ajuste de la última página y que otro cliente permanezca intacto.
