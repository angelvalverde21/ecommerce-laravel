# Directorios de clientes y proveedores

Los componentes Angular conservan las convenciones index, create, edit y form. Comparten estilos en contact-ui.scss, el listado ContactDirectoryComponent y el ciclo de guardado ContactEditor. Los servicios y los eventos públicos de cada módulo siguen separados.

## Listados

- Normalizan recursos anidados (user) y planos sin confundir el ID del contacto con el ID del usuario.
- Los enlaces de proveedores usan supplier.id. Los enlaces de clientes mantienen el contrato existente basado en user_id.
- El directorio recupera las páginas de proveedores antes de filtrar localmente. La búsqueda incluye nombre, teléfono, correo y documento; la paginación visual muestra 20 registros.
- Los errores permiten reintentar y no dejan la pantalla bloqueada en carga.

## Formularios

- Proveedores: contacto, identificación y, al crear, dirección principal. Las direcciones adicionales se gestionan desde la ficha.
- Clientes: contacto e identificación; los roles existentes se gestionan en edición.
- Los formularios compartidos no generan formularios HTML anidados. Los errores de validación se muestran dentro del editor, los datos se conservan al fallar y se evitan envíos duplicados.
- Crear un contacto no asigna automáticamente roles de acceso. Las cuentas nuevas reciben una contraseña aleatoria, sin usar el documento como contraseña.

## API

La creación de clientes ya no intenta leer identity_id sin validar. Devuelve el cliente con su usuario, y el navegador utiliza user_id para abrir la ficha.

Las consultas y actualizaciones de clientes y las actualizaciones de proveedores comprueban la pertenencia a la tienda. La edición de proveedores permite vaciar campos opcionales y valida el tipo de documento contra identities.

## Direcciones predeterminadas y documentos de compra

La dirección inicial del proveedor se recibe como un objeto address (primary, district_id), y se crea usando la relación polimórfica Supplier.addresses. No existe una columna de dirección dentro del proveedor.

El registro inicial utiliza AddressForm con la presentación supplier. Nombre, teléfono, correo y documento se completan una sola vez. SupplierCreate adapta esos valores al contrato de la API: contacto del proveedor y un objeto address con primary, district_id, secondary y references. La API reutiliza la identidad y teléfono validados para el contacto de la dirección predeterminada, dentro de la misma transacción.

addressLocationControls comparte las validaciones de ubicación entre el registro de direcciones y el de proveedores. AddressForm contiene campos y presentación; no ejecuta peticiones ni crea proveedores. SupplierForm queda reservado a la edición de los datos del proveedor existente.

SupplierAddressService conserva una sola dirección activa predeterminada por proveedor. El perfil permite cambiarla mediante una operación que bloquea el proveedor dentro de una transacción.

Compras y órdenes de abastecimiento guardan supplier_address_id, con clave foránea nullable hacia addresses. Una dirección disponible se selecciona automáticamente; si hay varias, el formulario muestra las opciones y propone la predeterminada. La edición conserva una selección anterior válida. La API valida tanto la tienda como la pertenencia de la dirección al proveedor.

La migración 2026_11_19_160000_link_supplier_addresses_to_purchases agrega esas claves a purchases y manufactures y normaliza los defaults existentes. No asigna direcciones a documentos históricos, porque no hay información que permita reconstruirlas con certeza.

## Validación

- Angular: contact-workflow.spec.ts, además de las regresiones de compras y pagos de production-module.
- Laravel: ContactDirectoriesTest, con SQLite aislado en memoria para creación, validación, identificación y separación entre tiendas.
