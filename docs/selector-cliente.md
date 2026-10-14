# Selector de cliente con buscador

Nueva solicitud permite buscar y seleccionar clientes por nombre, apellido o documento. Cada opción muestra Nombre completo — Documento. Cuando falta el documento, muestra Sin documento.

La búsqueda es local sobre las opciones recibidas por el formulario, ignora mayúsculas y acentos y acepta documentos con o sin puntos, espacios y guiones. Es posible combinar fragmentos de nombre y documento, por ejemplo maria 33095790.

Para guardar debe seleccionarse una opción. Escribir texto no confirma una selección; editar el texto de un cliente elegido borra el identificador anterior para evitar asociar la solicitud al cliente incorrecto.

El componente admite mouse, flechas, Enter, Escape y Tab, y utiliza los roles combobox/listbox. El select nativo conserva customer_guid como valor enviado. Sin JavaScript permanece disponible el selector normal con nombre y documento.

La selección se recupera con old(customer_guid) después de errores de validación. Al iniciar una solicitud desde la ficha de un cliente, el selector permanece deshabilitado y los campos ocultos conservan customer_guid y source_customer_guid.

## Integración y despliegue

Publicar app/Views/loan_applications/create.php, app/Views/components/customer_select.php y public/assets/js/customer-select.js. No requiere migraciones, endpoints ni dependencias nuevas.

El JavaScript mejora los select habilitados marcados con data-customer-search. Las opciones utilizan data-customer-name y data-customer-document para buscar y su value contiene el identificador del cliente.

## Verificación

```text
php vendor/phpunit/phpunit/phpunit --no-coverage tests/unit/CustomerSelectPresentationTest.php
node --test tests/js/customer-select.test.cjs
```

En Nueva solicitud, buscar un nombre sin acentos, un fragmento de documento y el documento completo con separadores. Elegir con mouse y luego con flechas/Enter. Comprobar el cierre con Escape y que un nombre escrito sin selección no permita enviar. Provocar un error en otro campo y verificar que se conserva el cliente seleccionado. Abrir Crear nueva desde la ficha y confirmar que el cliente queda bloqueado.
