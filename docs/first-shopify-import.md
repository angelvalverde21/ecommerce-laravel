# Primera importación de Shopify

Ruta de la interfaz: `/{store}/dashboard/initial-import/shopify`, menú **Primera
importación → Datos de Shopify**, inmediatamente después de Migración. Solo master,
ceo y admin, con pertenencia a la tienda excepto master, pueden consultar, iniciar o
reanudar una carga. La pantalla no expone credenciales, enlaces firmados ni payloads
de compradores. Actualiza contadores cada tres segundos durante una carga activa.

## Consulta de pedidos importados

Submenú **Primera importación → Pedidos**, ruta
`/{store}/dashboard/initial-import/orders`. Permite seleccionar la ejecución,
buscar por número/ID Shopify, cliente, correo o teléfono y filtrar todos,
comentarios no recuperados, con comentarios y sin revisar. Los contadores se calculan
desde la auditoría guardada, no desde una lista fija de IDs. Paginación de 25 pedidos.

Cada pedido abre `orders/{id-local}` con artículos, importes, estados, notas,
cliente y direcciones, pagos, envíos, reembolsos, datos adicionales originales y
timeline cronológico. Los eventos se paginan de 25 en 25, con filtros de comentarios
y sistema. Una advertencia de comentarios no recuperados no oculta eventos disponibles.
La búsqueda, el filtro, la página y la ejecución se conservan al volver al listado.
No se muestra el nombre del autor, pues la tienda trabaja con un solo usuario Shopify.
Debajo de la cabecera se reutiliza `OrderStatusStepperComponent`: Registrado,
Pago recibido, Pago confirmado, En preparación y Enviado. Se emplean las reglas
compartidas `orderProgress` sobre los estados y envíos importados; cancelación y
espera se presentan explícitamente. El stepper es informativo y no modifica Shopify
ni los estados guardados.
`FULFILLED` muestra Enviado sin exigir seguimiento de transporte; `IN_PROGRESS` y
`PARTIALLY_FULFILLED` permanecen En preparación. Se corrigió el caso #10952, cuyo
pedido y envío estaban FULFILLED pero el stepper esperaba un estado de transporte.
El stepper muestra las fechas reales con `DateShopify:'short'`: «Hoy 16:07»,
«Ayer 16:07», «Sábado 16:07». A los dos días se utiliza el nombre del día, nunca
«Anteayer». El pipe sin parámetros conserva «Sábado a las 16:07» y se utiliza en
la tabla de pedidos. El calendario relativo y la hora se calculan en America/Lima.
Registro usa createdAt; pago recibido usa la primera venta/captura exitosa;
confirmación usa el evento sale_success/capture_success cuando el pedido está PAID,
o la última transacción exitosa disponible. Preparación solo tiene fecha si existe
un evento fulfillment_pending. Envío usa el último fulfillment exitoso, o su evento
fulfillment_success. No se emplea updatedAt ni se inventan fechas para etapas
anteriores: si no hay registro, se muestra «Sin fecha».

Los artículos muestran miniaturas ampliables. La imagen se resuelve por IDs Shopify
de la misma ejecución: imagen de la línea, variante, imagen principal del producto
y primera imagen de su galería, en ese orden. Las relaciones se consultan por lotes,
sin buscar por nombres ni modificar los payloads guardados. `items[].display_image`
es solo información de presentación; ante una URL fallida se intenta la alternativa
y, si no hay imagen disponible, se muestra un marcador limpio. Validado en #10955:
los dos artículos tienen fotografías distintas y ambos enlaces respondieron HTTP 200.
`url_thumbnail` pide al CDN una imagen de hasta 128×152 px, conservando el parámetro
de versión; `url_large` conserva la imagen de origen. La tabla solo carga el thumbnail
con lazy loading. La ampliación reutiliza `ImagePreviewComponent` y su lightbox
Fancybox, con `previewVariant="large"` y `objectFit="contain"`. Large se carga al
abrir el lightbox. Si falla una miniatura, se cambia a la alternativa y se actualiza
su enlace large, sin descargarla por adelantado. Los demás usos conservan medium
como opción por defecto. En #10955 se verificaron thumbnails de 101×152 px y 6,284/9,417 bytes,
frente a originales de 152,893/1,711,566 bytes.

API de consulta bajo `/api/v1/{store}/dashboard/initial-import`:

- `GET /orders?timeline=unavailable&q=...&run_id=...&page=1`.
- `GET /orders/{id}`.
- `GET /orders/{id}/events?type=all|comments|system&page=1`.
- `GET /orders/{id}/attachments/{attachment_id}`: descarga autenticada, privada y
  limitada al pedido/ejecución/tienda; nunca admite una ruta proporcionada por el cliente.

Los adjuntos permiten ampliar la imagen, descargar el respaldo y abrir su URL de
Shopify cuando existe. Las vistas previas usan blobs autenticados, revocados al
destruir el componente. Los mensajes importados se muestran como texto y los datos
originales como JSON escapado, sin ejecutar el HTML de origen. Solo master, ceo y
admin tienen acceso, con la misma pertenencia a tienda que la pantalla de importación.
Esta sección no modifica registros ni ejecuta consultas a Shopify; no necesita
migraciones adicionales. Validación sobre la base real: 9,930 pedidos, 2,420 con
comentarios no recuperados, 338 con comentarios recuperados y ninguno sin revisar.

## Buscar nuevos pedidos

El listado incluye **Buscar nuevos pedidos**. La acción agrega los pedidos que
faltan a la ejecución seleccionada y muestra su progreso en segundo plano.
`POST /orders/updates` recibe `run_id`; `GET /orders/updates/{id}` consulta el estado.
Ambos conservan los permisos y el aislamiento por tienda de la sección.

Migración: `2026_11_17_130000_create_import_shopify_order_updates_table.php`.
La tabla `import_shopify_order_updates` conserva responsable, intervalo fijo de
consulta, cursor, pedidos revisados/agregados, punto inicial de tareas, estados y
errores. Las fechas del intervalo son DATETIME para evitar los valores por defecto
implícitos de TIMESTAMP en versiones antiguas de MySQL.

La primera consulta parte del inicio de la carga original menos un día; las siguientes
parten del límite superior de la última búsqueda completada, también con un día de
solapamiento. El límite superior se fija al pulsar el botón. No se limita a «hoy»,
para recuperar pedidos que llegaron durante noches o días sin consultas. Solo se
insertan IDs ausentes en esa ejecución: los pedidos existentes no se sobrescriben.
Repetir un clic mientras hay una búsqueda activa devuelve la misma operación.

Se consultan páginas completas de 50 pedidos con los campos originales, incluyendo
notas, atributos, importes, direcciones, transacciones, envíos y reembolsos. Se usa el
runner existente para recuperar todas las páginas de artículos, descuentos, eventos,
metadatos, detalles de envíos/reembolsos y archivos del timeline. Clientes, variantes
y productos referenciados que faltan se hidratan mediante tareas snapshot de hasta
50 IDs; sus direcciones, imágenes y metadatos también se recuperan. No se reinician
los cuatro exports completos ni se escriben las tablas definitivas.

Las páginas se confirman en una transacción junto con su cursor y contadores. La
operación solo termina cuando todos sus detalles y adjuntos han terminado; un error
mantiene el checkpoint y habilita **Reintentar búsqueda**. El mismo procesador
`RunInitialShopifyImport` conserva los locks y la cola dedicada. La pantalla consulta
el progreso cada tres segundos, evita clics repetidos y recarga el listado al terminar.

Verificación real de la consulta completa: 86 puntos solicitados para un lote de 50,
con capacidad de 2,000 puntos y recuperación de 100 puntos/s en esta tienda. Se
mantiene la pausa mínima de un segundo y la adaptación al presupuesto de Shopify.
El procesador local actualizado registra su actividad en
`storage/logs/import-shopify-worker-new-orders.log`.

La primera ejecución real desde la interfaz terminó correctamente: 2 pedidos
revisados, 1 agregado y sus 8 tareas de detalle completadas, sin errores. Conservó
la ejecución original y sus registros previos; no se inició otra exportación completa.

## Separación de datos

Este flujo NO invoca el traslado a pedidos definitivos ni la sincronización antigua
del catálogo. Solo escribe en `import_shopify_*` y en la infraestructura de colas/cache.
No crea usuarios, clientes, productos, variantes, pedidos, pagos ni movimientos de
inventario en sus tablas finales. Cada carga conserva su propio UUID y puede revisarse
en MySQL antes de cualquier conciliación posterior.

Migración aplicada localmente:
`2026_11_16_120000_create_import_shopify_staging_tables.php`.
Es reanudable después de una interrupción de DDL y utiliza nombres de índices
compatibles con el límite de identificadores de MySQL.

| Tablas con prefijo `import_shopify_` | Contenido |
| --- | --- |
| `runs`, `tasks` | Ejecución, tienda, conexión original, solicitante, progreso, cursores y checkpoints |
| `orders` | Pedido original con notas, etiquetas, estados, monedas, fechas, importes, impuestos, atributos y datos de comprador |
| `order_items` | Todas las líneas, cantidades originales/actuales, SKU, variante Shopify, precios, impuestos y asignaciones de descuentos |
| `shipping_lines`, `discounts` | Envíos cobrados y aplicaciones de descuentos, sin límite de diez líneas |
| `transactions` | Transacciones originales, gateway, estado, autorizaciones, importes y recibo JSON |
| `fulfillments`, `fulfillment_items`, `fulfillment_events` | Envíos, tracking, artículos enviados y eventos de entrega |
| `refunds`, `refund_items`, `refund_shipping_lines`, `refund_adjustments` | Reembolsos, artículos, envíos y ajustes asociados |
| `products`, `variants`, `images` | Catálogo, opciones, IDs, SKU, precios, cantidad de inventario disponible e información/URLs de imágenes |
| `customers`, `addresses` | Clientes, notas, etiquetas y direcciones; envío/facturación se conservan como snapshots de cada pedido |
| `metafields`, `events`, `records` | Metadatos visibles para la app, comentarios/eventos y espacio de preparación para otros registros |
| `event_attachments` | Archivos adjuntos de comentarios, vínculo al pedido/evento, copia privada, tamaño, MIME, SHA-256 y estado de respaldo |

Las tablas de entidades guardan `run_id`, `shopify_id`, `parent_shopify_id` y `payload`
JSON original. La restricción única por carga e ID evita duplicados al reanudar.
Descuentos sin ID propio utilizan el ID del pedido y el índice original del descuento.
Las direcciones de pedidos utilizan una clave por pedido y propósito para no perder
un domicilio histórico compartido. Los importes originales permanecen como cadenas
dentro del JSON, incluyendo shopMoney y presentmentMoney; no se convierten a float.
No se asocian variantes locales en esta etapa: se conservan los IDs Shopify para la
conciliación posterior. Las imágenes del catálogo se reciben como metadatos/URLs.
Los archivos adjuntos al timeline sí se descargan completos, incluidos comprobantes
de pago e imágenes/PDF que Shopify exponga como CommentEventAttachment.
Los archivos exclusivos de una aplicación externa que Shopify no exponga como adjuntos
requieren el acceso a esa aplicación. No se importa el resto de recursos Shopify ajenos a reconstruir
pedidos y su catálogo/clientes.

## Respaldo del timeline (versión 2)

Migración adicional: `2026_11_17_100000_create_import_shopify_event_attachments_table.php`.
Se conservan los mensajes HTML y raw, ID y fecha originales, acción, aplicación,
atribución, alertas, edición, referencias al recurso y embeds. Los eventos de sistema
incluyen autor textual, argumentos, mensajes secundarios, additionalData y additionalContent.
La relación `import_shopify_events.parent_shopify_id` apunta al ID Shopify del pedido.

Los comentarios incluyen todos los metadatos de `attachments`, sin truncar esa lista.
En `import_shopify_event_attachments`, `parent_shopify_id` apunta al comentario,
`order_shopify_id` al pedido y `shopify_id` al archivo original. La identidad incluye
comentario y archivo para conservar referencias si un archivo se reutiliza.
`payload` conserva nombre, extensión, tamaño, URL original y metadatos de imagen.
La copia del archivo entregado por Shopify se guarda sin transformaciones locales en
`storage/app/private/import-shopify/{UUID}/attachments/{hash}.original`;
`disk`, `local_path`, `bytes`, `mime_type`, `sha256` y `downloaded_at` permiten
recuperarla y comprobar su integridad sin depender de la URL remota. No se modifica
el archivo descargado ni se publica mediante `storage/public`. Los enlaces firmados y los
archivos no se exponen en la API de progreso.

Cada descarga es una tarea reanudable independiente, con archivo temporal, validación
de tamaño HTTP y publicación por rename. Se usa HTTPS, destinos CDN Shopify reconocidos
y el bucket exacto `shopify-private-shop-assets.storage.googleapis.com`,
validación de cada redirección, timeout de 120 s y límite de 100 MiB por adjunto.
Una descarga fallida queda `failed` y su tarea `blocked`; las demás continúan.
Si queda algún bloqueo, la ejecución termina en `failed`, nunca en `completed`.
Reanudar reintenta esas tareas; URLs caducadas o nuevos permisos se pueden recuperar
volviendo a consultar exclusivamente el timeline:

```powershell
C:/xampp/php/php.exe artisan shopify:refresh-import-timeline {UUID}
```

El comando acepta cargas terminadas o detenidas, toma locks por tienda/ejecución y
reencola solo los eventos de los pedidos importados, además de verificar sus adjuntos.
Conserva todos los demás registros y checkpoints. Reiniciar el trabajador tras
actualizar el código, antes de ejecutar el comando. La pantalla muestra el total
de adjuntos, cuántos tienen respaldo y cuántos necesitan revisión.

El CDN puede recomprimir imágenes: `payload.size` conserva el tamaño declarado por
Shopify; `bytes` representa la descarga real. `download_metadata` guarda tamaño HTTP,
ETag, Last-Modified, tipo y coincidencia con el tamaño declarado. Ante una diferencia
solo se acepta una imagen íntegramente decodificable, con Content-Length correcto y
dimensiones originales coincidentes si Shopify las proporciona. No se presenta esa
copia como idéntica byte por byte al archivo subido por el empleado. Los PDF y otros
archivos deben coincidir con el tamaño declarado.

La auditoría por pedido se conserva en `import_shopify_tasks.metadata` para las tareas
`events`: bandera `has_timeline_comment`, comentarios recuperados, fecha y disponibilidad.
Se siguen todas las páginas antes de registrar `unavailable_comments`. La pantalla
avisa cuántos pedidos tienen comentarios que Shopify no devuelve; una ejecución
terminada con ese aviso significa que se copiaron los datos disponibles, no que todo
el historial original se haya recuperado. Migraciones adicionales:
`2026_11_17_110000_add_timeline_audit_to_import_shopify_tasks.php` y
`2026_11_17_120000_add_download_metadata_to_import_shopify_attachments.php`.

**Limitaciones comprobadas:** el autor estructurado de CommentEvent requiere
`read_users`, permiso que Shopify restringe según el tipo de aplicación/plan.
Sin ese permiso no se solicita el campo protegido: se conserva el mensaje íntegro y
se registra `_import.author_status = requires_read_users`. Con permiso se guarda
`staffAuthor { id name }`. No se inventa un nombre a partir del texto.
La versión de extracción y su fecha se guardan aparte en `_import`, sin cambiar
`createdAt`. Shopify puede no exponer eventos antiguos/eliminados o datos privados de
otras aplicaciones. Este respaldo preserva lo que devuelve la API, no promete recrear
en Shopify su auditoría interna ni recuperar archivos que ya hayan sido eliminados.

Validación real: un lote de 250 pedidos recientes devolvió 217 comentarios y 100
referencias a archivos del CDN, con coste solicitado/real de 33 puntos. El campo
`author` de los comentarios respondió ACCESS_DENIED sin `read_users`.

Resultado de la recuperación local: 6,668 eventos y 342 adjuntos respaldados
(260,492,610 bytes). Se comprobaron los 342 archivos por existencia, tamaño y SHA-256,
sin faltantes ni alteraciones. Hay 2,420 pedidos con `hasTimelineComment = true`
sin comentarios devueltos por la API; también se comprobaron filtros explícitos de
comentarios y fechas en muestras históricas, con resultados vacíos. Se mantiene la
advertencia de cobertura, sin atribuir todos los casos a una causa no confirmada.
Shopify documenta una [retención de eventos de un año](https://shopify.dev/changelog/posts/events-data-now-limited-to-one-year-retention),
pero esa regla no explica por sí sola todos los ejemplos observados.

## Obtención y límites

Las cuatro raíces (pedidos, productos, variantes y clientes) se exportan mediante
Bulk Operations sin filtros de fecha/estado, conservando cancelados. Así no se depende
del límite de paginación normal del historial. Solo se acepta una exportación
COMPLETED con conteos verificables; nunca `partialDataUrl`.

Los detalles se consultan mediante `nodes(ids: ...)` en lotes de hasta 250 recursos
de la misma clase, con 25 nodos por conexión y página. Se siguen `hasNextPage`
y `endCursor` hasta el final; los recursos con páginas adicionales continúan desde
su cursor individual. Cada lote se confirma transaccionalmente y las tareas siguen
contabilizándose por recurso verificado, no por petición HTTP. Así se evita hacer
una consulta independiente por cada pedido/envío cuando los detalles están vacíos.
La espera mínima se aplica en el transporte HTTP, sin duplicarla en el bucle del
trabajador. Se validaron los costes reales de lotes de 250 recursos antes de
aplicarlos a la carga en curso: dentro del presupuesto informado por Shopify.
No se truncan variantes, líneas, direcciones ni detalles
a un primer bloque. Los campos de listas no paginadas (transacciones, fulfillments y
refunds) no llevan argumentos `first` que recorten los resultados; sus conexiones
de detalle sí se recuperan íntegramente mediante tareas independientes.

Se reutiliza la autenticación y el transporte de `ShopifyOrderBulkSource`. La conexión
antigua conserva su versión y su pausa de 50 ms; esta carga tiene una versión separada
por defecto `2026-10` y espera al menos un segundo antes de cada consulta, ampliando
la espera con el presupuesto devuelto por Shopify. Se consideran hasta 2,000 puntos
de capacidad, pero se respeta un máximo menor si lo comunica la API. Las respuestas
429/THROTTLED aplican Retry-After y recuperación de coste, con hasta cinco intentos.
Fallos de red o mutaciones ambiguas detienen la carga, sin iniciar reintentos ciegos.

Referencia: [límites GraphQL](https://shopify.dev/docs/apps/build/apis/graphql-admin/rate-limits)
y [exportaciones masivas](https://shopify.dev/docs/apps/build/apis/graphql-admin/bulk-operations/queries).

Los archivos JSONL se almacenan privadamente en
`storage/app/private/import-shopify/{UUID}/{task}.jsonl`. Se leen bloques de 250 raíces,
guardando datos y offset en una transacción. Las páginas de detalle y sus cursores se
confirman juntas. Archivos incompletos o JSON inválido se vuelven a descargar al
reanudar; los upserts evitan duplicados. Una carga fallida mantiene la exclusividad
de su tienda hasta reanudarse, evitando dos inicializaciones simultáneas.

La API no proporciona una foto atómica de toda la tienda: si los recursos cambian
durante una carga de varios minutos, sus consultas pueden reflejar momentos distintos.
Los campos protegidos o metadatos de otras aplicaciones dependen del acceso que
Shopify conceda; valores ocultos/null no pueden recuperarse mediante el importador.
El proceso requiere read_all_orders más read_orders/write_orders, read_products o
write_products, read_customers o write_customers y acceso a datos protegidos de clientes.

## Procesador

La petición web solo encola `RunInitialShopifyImport` en la conexión dedicada
`import_shopify`. El trabajo avanza en un while de hasta 20 segundos por turno y se
libera por un segundo entre turnos. Timeout de tarea: 900 s; retry_after: 1,200 s.
Un lock por ejecución protege contra trabajadores concurrentes y los checkpoints
permiten continuar tras un reinicio. Las consultas HTTP tienen timeout de 60 s y las
descargas, de 600 s. Un timeout marca la ejecución como interrumpida.

Se dejó un procesador local oculto activo. Después de reiniciar el equipo/servidor,
debe volver a ejecutarse; en producción se debe mantener con el administrador de
procesos del servidor:

```powershell
C:/xampp/php/php.exe artisan queue:work import_shopify --queue=import-shopify --sleep=1 --timeout=900 --tries=0
```

Configuración local añadida: `SHOPIFY_INITIAL_IMPORT_STORE_SLUG=sorelle`.
Opcional: `SHOPIFY_INITIAL_IMPORT_API_VERSION=2026-10`. No se cambió la versión de la
conexión Shopify utilizada por las pantallas anteriores ni se habilitó el traslado
antiguo a tablas definitivas.

## Lectura del listado para empaques

El listado prioriza pedido, cliente/destino, artículos con variantes y cantidades,
preparación y pago. Las cantidades múltiples se destacan; las imágenes reutilizan
el visor existente: thumbnail en el listado y original grande al abrirlo. Se muestran
hasta tres conceptos por pedido y un enlace al detalle cuando hay más.

Los filtros Todos, Por empacar, En preparación y Enviados se calculan sobre la
ejecución seleccionada. Por empacar requiere PAID + UNFULFILLED; los cancelados,
en espera y programados se separan. Los envíos parciales aparecen En preparación
con una advertencia para revisar lo pendiente en el detalle. Los importes y estados
de pago se conservan como información independiente de la preparación.

Los artículos usan currentQuantity (quantity si no existe); las líneas con cantidad
actual cero no se cuentan. Estas son cantidades vigentes del pedido, no una lista
de unidades pendientes de un envío parcial. La consulta enriquece exclusivamente
la página actual mediante lotes, con imágenes relacionadas por IDs y ejecución.
No actualiza pedidos, envíos ni tablas definitivas. El historial recuperado y la
selección de importación siguen disponibles en Más filtros. La URL conserva los
filtros operativos al abrir un pedido y volver al listado.

## Validación

- 36 pruebas de servidor entre InitialShopifyImportTest y ShopifyOrderImportTest:
  permisos/tienda, cola asíncrona, exclusividad, payloads, paginación, reanudación,
  idempotencia, recuperación de archivos incompletos, lotes de varios recursos con
  cursores independientes, límites, archivos originales/verificación, privacidad,
  adjuntos incompletos, reanudación y recuperación selectiva del timeline.
- 42 pruebas Angular: menú, petición única, consulta de progreso, errores y reanudación,
  incluyendo la pantalla de migración anterior. Compilación de desarrollo exitosa.
- Consultas de validación vacías contra la conexión Shopify real: campos y permisos
  comprobados; capacidad observada de 2,000 puntos y recuperación de 100 puntos/s.
- Prueba local de consumo de cola sin carga real de datos, finalizada correctamente.
- La carga real se inició posteriormente desde el botón de la nueva sección. La
  optimización de consultas se aplicó sobre esa misma ejecución conservando sus
  registros y checkpoints. El procesador local actual registra su salida en
  `storage/logs/import-shopify-worker-timeline-final.log` después de la recuperación
  y verificación de adjuntos.
