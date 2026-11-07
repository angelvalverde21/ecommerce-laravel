# Migración de pedidos de Shopify

Estado de esta entrega: sección y scripts preparados, ejecución deshabilitada. No se aplicó la migración de estructura, no se modificó `.env` y no se descargaron ni importaron pedidos reales. Las pruebas utilizan SQLite en memoria y respuestas simuladas.

## Pantalla

Menú **Migración → Pedidos de Shopify**, ruta `/{store}/dashboard/migrations/shopify-orders`.

La pantalla revisa la configuración y el esquema locales, muestra las últimas diez ejecuciones de la tienda y mantiene deshabilitado el botón de importación. El único endpoint es `GET /api/v1/{store}/dashboard/migrations/shopify/orders`: no llama a Shopify ni escribe datos. Acceso para `master`, `ceo` y `admin`; salvo `master`, el usuario debe pertenecer a la tienda.

## Integración encontrada

`app/Services/Shopify/ShopifyOrderService.php` ya consulta pedidos para las pantallas existentes. Su consulta de listado excluye cancelados y su selección de artículos limita las líneas. Se conserva ese servicio y se añade `app/Services/Shopify/OrderImport/` para el historial completo, reutilizando las claves de conexión existentes en `config/shopify.php`.

El importador solicita una exportación masiva GraphQL sin filtros de fecha o cancelación y sin limitar las líneas. Shopify genera un archivo JSONL con las conexiones anidadas, evitando paginar todo el historial desde una petición web. [Documentación de exportaciones masivas](https://shopify.dev/docs/apps/build/apis/graphql-admin/bulk-operations/queries).

## Estructura preparada

Archivo pendiente: `database/migrations/2026_11_06_150000_prepare_shopify_order_import.php`.

| Tabla | Uso |
| --- | --- |
| `orders` | Amplía únicamente las columnas ausentes: tienda, referencia Shopify, variantes, entrega, importes, estado y moneda. Índice único por tienda e ID Shopify. Compatible con la tabla antigua que solo tenía ID y fechas, siempre que esté vacía. |
| `addresses` | Dirección de envío y facturación asociadas mediante la relación polimórfica existente. Añade propósito y ubicación internacional; permite distrito nulo cuando no hay equivalencia local verificable. |
| `order_items` | Nueva tabla de líneas, cantidades, importes, moneda, ID Shopify y variante local opcional. |
| `order_shopify_details` | Nueva tabla de datos de origen: estados Shopify, fecha, cliente, notas, etiquetas, direcciones originales y transportistas. Los artículos originales se conservan en `order_items.source_payload`. |
| `shopify_order_imports` | Nueva tabla de ejecuciones, operación remota, fase, conteos esperados, contadores y puntos de reanudación. |
| `shopify_order_import_rows` | Nueva tabla de preparación del JSONL. Permite reconstruir todos los hijos aunque aparezcan antes de su pedido. |

No se borran pedidos. Si existen pedidos antiguos sin tienda o referencias Shopify duplicadas, el cambio de estructura se detiene antes de modificarlos para resolver su atribución. La reversión de este cambio requiere restaurar el respaldo previo; `down()` no elimina silenciosamente información histórica.

## Ejecución futura: no realizada en esta entrega

1. Respaldar la base de destino y comprobar que contiene los últimos datos. Probar primero con una copia de esa base.
2. Aplicar específicamente el archivo de estructura pendiente y revisar las tablas; no ejecutar indiscriminadamente todas las migraciones pendientes del proyecto:

   ```powershell
   php artisan migrate --path=database/migrations/2026_11_06_150000_prepare_shopify_order_import.php
   ```

3. Revisar la conexión existente `SHOPIFY_STORE`, `SHOPIFY_API_VERSION` y `SHOPIFY_ACCESS_TOKEN`. El dominio debe ser el prefijo de la tienda o su dominio `*.myshopify.com`.
4. Conceder `read_all_orders` y `read_orders` o `write_orders` a la app. Shopify restringe por defecto el historial a 60 días; el script comprueba estos permisos antes de crear la exportación. Revisar también el acceso de la app a datos protegidos de clientes y a exportaciones masivas. [Permisos de pedidos](https://shopify.dev/docs/api/admin-graphql/latest/queries/order), [acceso a operaciones masivas](https://shopify.dev/docs/apps/build/apis/graphql-admin/bulk-operations/queries#enabling-access-to-bulk-operations).
5. Vincular explícitamente la conexión Shopify a la tienda local y habilitar la ejecución cuando se autorice:

   ```dotenv
   SHOPIFY_ORDER_IMPORT_STORE_SLUG=sorelle
   SHOPIFY_ORDER_IMPORT_ENABLED=true
   ```

   Si Laravel utiliza configuración en caché, actualizar esa caché después de cambiar la configuración. Estos valores no se añadieron a `.env` en esta entrega; por defecto la ejecución sigue deshabilitada.

6. Consultar únicamente la preparación local:

   ```powershell
   php artisan shopify:orders-import sorelle
   ```

7. Para importar, únicamente cuando corresponda ejecutar el traslado:

   ```powershell
   php artisan shopify:orders-import sorelle --execute
   ```

   El comando imprime el UUID de ejecución. `--wait` controla los segundos de espera por la exportación remota, por defecto 600, entre 1 y 3600. Si la exportación continúa, el comando lo indica y se puede reanudar:

   ```powershell
   php artisan shopify:orders-import sorelle --execute --resume=UUID_DE_LA_EJECUCION
   ```

   Ese límite no interrumpe a mitad del guardado local: una vez descargado el archivo, se procesa desde consola. No hay endpoint web que inicie el trabajo.

## Comportamiento de la importación

- La solicitud de inicio y las consultas de estado tienen un bucle de hasta cinco intentos ante HTTP `429` o errores GraphQL exclusivamente `THROTTLED`, sin resultados parciales. Respeta `Retry-After` (segundos o fecha HTTP), calcula la recuperación con `requestedQueryCost`, `currentlyAvailable` y `restoreRate`, y aplica espera exponencial si no hay información suficiente. Si Shopify exige más de 60 segundos de pausa o se agotan los intentos, detiene el proceso para reanudar más tarde; nunca reintenta antes del plazo indicado. No reintenta automáticamente fallos de red, respuestas `5xx` ni mutaciones con datos parciales, porque podrían ocultar una exportación ya iniciada. Este control se limita al nuevo importador. La paginación de `ShopifyBaseService::getDataFromShopify` conserva su implementación existente, cuya pausa es de 50 ms (`usleep(50000)`). Las operaciones masivas ejecutan la consulta de exportación de forma asíncrona, mientras las llamadas de inicio y estado sí están sujetas a los límites normales. [Límites de operaciones masivas](https://shopify.dev/docs/apps/build/apis/graphql-admin/bulk-operations/queries#how-bulk-operations-fit-within-the-admin-api-rate-limits).
- Solo procesa exportaciones `COMPLETED`; nunca utiliza `partialDataUrl`. Verifica los conteos de pedidos y de registros antes de guardar en `orders`. Shopify distingue el conteo raíz del total de objetos procesados. [Campos de BulkOperation](https://shopify.dev/docs/api/admin-graphql/latest/objects/BulkOperation).
- Descarga a `storage/app/private/shopify-order-imports/{UUID}.jsonl`. El archivo no se sirve públicamente. La petición de descarga no lleva el token Shopify; la pantalla y el comando no muestran URLs firmadas, credenciales o datos personales.
- Guarda la preparación por lotes y cada pedido completo en una transacción junto a sus artículos, direcciones y checkpoint. Si falla un pedido, los anteriores permanecen confirmados y ese pedido no queda a medias.
- La ejecución puede retomarse tras una interrupción. Una vez descargado, conserva los conteos esperados para reanudar sin depender de la vigencia del enlace remoto. Una exportación remota fallida, cancelada o expirada detiene la ejecución; al reanudar solicita una exportación nueva.
- El bloqueo de consola y la restricción de ejecución activa por tienda evitan iniciar dos procesos independientes. Una ejecución pendiente o fallida debe reanudarse con su UUID.
- Omite pedidos existentes por tienda e ID Shopify, reconociendo tanto el GID completo como su parte numérica. No sobrescribe cambios locales. Los IDs propios de la base se generan nuevamente.
- Vincula variantes por SKU solo si existe una coincidencia inequívoca dentro de la tienda local. Conserva las líneas sin coincidencia o ambiguas con `variant_id = NULL`, sus IDs Shopify y su contenido original; incrementa el contador para revisión.
- Conserva importes en moneda de la tienda Shopify, sin conversiones, y también los conjuntos de importes originales en JSON. Usa decimales, no operaciones de coma flotante. Un importe incompatible con la precisión local detiene el pedido y requiere revisión antes de continuar.
- Conserva las fechas de origen convertidas a la zona horaria de Laravel. Mapea el estado operativo local y mantiene separados los estados financiero y de entrega originales. La cancelación tiene prioridad; luego envío, preparación y pago.
- Las direcciones internacionales o sin ubigeo comprobable quedan con distrito nulo y se contabilizan para revisión. Solo asocia un distrito peruano si encuentra un código de seis dígitos existente en `districts`, en el código postal o en atributos `ubigeo`, `district_id` o `codigo_ubigeo`. No asigna un distrito por defecto.
- No crea productos, variantes, usuarios ni movimientos históricos de inventario/Kardex. No convierte el estado pagado en un nuevo cobro local ni asigna un courier por coincidencia aproximada. Conserva cliente, transportista y estados de pago como datos de origen para conciliación. La consulta no importa eventos, transacciones, reembolsos individuales ni el historial de cada fulfillment; conserva sus estados e importes resumidos.

Tras ejecutar en el futuro, revisar los conteos, las líneas sin variante, los distritos pendientes, monedas y estados antes de utilizar estos pedidos como carga operativa. Mantener privados los archivos y la preparación JSONL, que contienen información personal; definir su retención después de conciliar la migración.

## Validación local realizada

- `php artisan test --compact --filter=ShopifyOrderImportTest`: SQLite en memoria y Shopify simulado; comprueba preparación sin escrituras ni red, permisos y tienda, compatibilidad con esquema antiguo vacío, más de diez líneas, cancelados antiguos, asociación por SKU, transacciones, checkpoints, duplicados, descargas incompletas y ausencia del token en la descarga.
- Pruebas Angular de la pantalla: botón bloqueado, revisión local, errores, prevención de solicitudes duplicadas y permisos del menú.
- Pruebas del límite de solicitudes: recuperación de puntos, `Retry-After`, máximo de cinco intentos y ausencia de reintentos automáticos en mutaciones ambiguas. Las esperas están simuladas en las pruebas.
- Compilación de Angular y formato de los archivos PHP nuevos.
