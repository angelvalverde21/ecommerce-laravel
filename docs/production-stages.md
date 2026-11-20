# Etapas de producción

## Menú y vista de trabajo por sección

Cada sección presenta una cabecera compacta. En escritorio, el título y el
estado comparten fila con el selector de sección y Siguiente proceso. Las pestañas
usan un subrayado y la navegación usa botones neutros; el color principal se reserva
para las acciones de trabajo. Retroceder aparece encima y fuera de la cabecera,
vuelve al menú de etapas y mantiene la protección de borradores.
En móvil el selector y Siguiente proceso permanecen juntos debajo del título.
Configurar secciones aparece solamente en el menú de etapas. El listado de
producciones se abre desde el menú lateral.

La entrada de una producción abre `overview`: cabecera, resumen y menú de etapas.
El resumen y el menú comparten la escala visual del espacio de trabajo: título
principal de 28 px, controles de 48 px, valores de resumen de 21 px y nombres de
etapa de 15 px en escritorio. En móvil se conservan cuatro columnas, con nombres
de 13 px y estados abreviados para mantener la lectura. La cabecera y los indicadores
se reorganizan sin desbordamiento horizontal.
Elegir una tarjeta abre `purchases?stage=<slug>`, conservando los enlaces anteriores,
y muestra únicamente el espacio de trabajo de esa sección, sin el menú ni los totales.
El espacio de trabajo tiene un selector de sección, Retroceder y Siguiente proceso,
y un acceso para volver al menú. Solo aparecen las secciones incluidas en esa producción.

Las pestañas base son Compras, Seguimiento e Historial. Compras se abre por defecto.
Los enlaces aceptan `view=purchases`, `view=process` (seguimiento) y `view=history`.
Se retiró la pestaña Datos de la etapa. Culminar etapa es un botón oscuro fijo,
visible al desplazarse, que abre el diálogo de cierre con los campos de la etapa.
Archivos se abre desde la cabecera en un diálogo independiente.
Seguimiento de compras muestra solo las compras de la sección actual con
`delivery_tracking.expected_on`, con filtros de pendientes, vencidas y recibidas.
Gestionar una compra permite regresar a la misma sección y pestaña.
Los formularios permanecen en el diálogo de cierre. Corte incorpora una pestaña
Productos (`view=products`) con el componente existente de variantes y cantidades.
Recepción incorpora Recepciones (`view=receptions`), que reutiliza el bloque completo
de hojas de recepción, ingresos, salidas e historial de inventario. Compras sigue
siendo la pestaña inicial en ambas etapas. Los enlaces del resumen abren estas
pestañas cuando la etapa correspondiente está incluida en la producción. El valor de enlace
`view=process` se conserva para mantener los enlaces existentes.
Compras muestra los gastos de esa sección y la clasificación de compras.
Cambiar de pestaña conserva el borrador; cambiar de sección o salir advierte sobre
datos sin guardar. Durante un guardado no se permite abandonar la sección.

La cabecera agrupa título y estado, separa la navegación en una barra alineada y
mantiene las pestañas en una franja propia. Los controles tienen un mínimo de 48 px,
texto de 14–15 px y disposición vertical en móvil. El selector usa un control reactivo
independiente del formulario para mantener la sección correcta al abrir enlaces
directos, cambiar de etapa o actualizar la lista de opciones. Los tamaños de texto
de compras, formularios e historial se ajustan a la misma escala de lectura.

Historial presenta un timeline descendente de los eventos reales de la sección:
estado, responsable, fechas, muestras, observaciones y configuración, con actor y
valores anteriores/nuevos. También muestra la fecha de registro de las compras
actualmente asociadas que disponen de fecha de creación. No inventa eventos anteriores,
ni interpreta importes o proveedores actuales como sus valores históricos.
Los eventos usan `production_stage_events`, ligados al avance de la producción;
esta vista no requiere migraciones ni modifica datos existentes.

Las secciones siguientes documentan las decisiones previas del módulo.

## Secciones particulares por producción (20/11/2026)

`production_stages` funciona como catálogo de ocho plantillas predeterminadas.
La selección particular pertenece a `production_stage_progress`, que ya contiene
`manufacture_id` y `stage_id`, y ahora añade `is_enabled`. No se agrega un propietario
al catálogo compartido ni se duplican las definiciones de las ocho plantillas.

Al crear una producción se presentan las ocho opciones marcadas. La API acepta
`stage_ids`; si se omite, incluye todas. Solo crea registros de las secciones elegidas.
El detalle permite ajustar esa selección con Configurar secciones. Quitar una sección
la archiva sin borrar avances ni eventos; volver a seleccionarla recupera sus datos.
Las secciones con compras deben reasignar esas compras antes de poder quitarse.
Costura se selecciona, inicia y completa independientemente de Corte. Cambiar o quitar
Corte no altera el estado de Costura. La revisión `manufactures.stage_configuration_revision`
evita sobrescribir configuraciones concurrentes y cada cambio se registra en el historial.

El listado, el stepper, la navegación, las compras y el dashboard usan únicamente las
secciones activas de cada producción. La API rechaza compras, asignaciones o avances
en secciones excluidas. La clasificación automática solo utiliza secciones incluidas;
sin Otros, las compras sin una coincidencia quedan pendientes. Los accesos del resumen
a productos y recepciones permanecen disponibles aunque se omitan Corte o Recepción.

Migración local aplicada: `2026_11_20_100000_make_production_sections_selectable.php`.
Conservó las 80 secciones particulares de las diez producciones existentes, todas
activas inicialmente, y sus avances, eventos y compras. Respaldo y comprobación en
`storage/app/private/backups/production-section-selection-*/`.
No se aplicaron otras migraciones ni se desplegó en hosting.

Lo que sigue describe la evolución anterior del módulo.

## Flujo inicial de ocho etapas (19/11/2026)

El detalle reemplaza las cuatro tarjetas superiores por ocho tarjetas de navegación:
Moldes y ploteo, Muestras, Telas e insumos, Corte, Costura, Acabados,
Recepción de mercadería y Otros. Tanto en escritorio como en móvil se distribuyen
en dos filas y cuatro columnas.
Los importes y cantidades permanecen en un resumen compacto; el seguimiento de
entregas conserva su acceso. Corte incluye el editor existente de productos y variantes.

`production_stages` conserva el catálogo compartido. Cada producción nueva genera
ocho filas en `production_stage_progress`, dentro de su transacción de creación,
con unicidad por `manufacture_id` y `stage_id`. La migración específica
`2026_11_19_170000_expand_production_workflow.php` completa los registros faltantes
en producciones existentes, sin modificar avances, eventos ni asignaciones de compras.
Se aplicó localmente y se verificaron las diez producciones existentes. El respaldo
y `verification.json` están en `storage/app/private/backups/production-workflow-*/`.
No se aplicaron otras migraciones ni se desplegó en hosting.

Las seis etapas originales conservan los estados calculados por compras y su cierre
manual. La dependencia original de Costura respecto a Corte se retiró el 20/11/2026:
ambas secciones funcionan de manera independiente, incluido el dashboard.

Recepción y Otros permiten actualizar su estado manualmente, de forma independiente
y con revisión de concurrencia e historial. La recepción mantiene sus documentos,
cantidades e ingresos existentes; cambiar el estado no crea movimientos de stock.
Otros agrupa compras sin clasificación y no condiciona el estado global de producción.

La migración es de datos y se conserva hacia adelante: su reversión requiere una
migración correctiva para evitar borrar compras, avances o eventos.

Las secciones siguientes documentan el comportamiento anterior a este cambio.

### Interfaz guiada

Las ocho tarjetas conservan dos filas y cuatro columnas como selector compacto.
Cada tarjeta es un único botón con nombre, estado y resumen; abre su propia etapa,
incluida Costura. Las instrucciones y acciones están en el panel de trabajo.
El panel permite navegar con Anterior y Siguiente sin exigir completar una etapa.
Los cambios de estado se realizan desde el panel de la etapa con Guardar cambios,
Completar y Reabrir. Al navegar, el panel recibe foco y desplazamiento con respeto
a la preferencia de movimiento reducido. Recepción también usa ese panel; sus
enlaces anteriores siguen disponibles.

Muestras permite registrar cantidad, detalles, aprobación, enlaces a fotos y comentarios;
Costura permite registrar taller y fecha; Corte conserva el editor de productos.
Los errores aparecen junto a los campos y no borran el formulario. Un guardado
correcto ofrece continuar con la siguiente etapa disponible. Al navegar se mantiene
visible el selector en lugar de saltar por debajo de todas las tarjetas. Los cambios automáticos
de estado por compras actualizan la revisión sin perder el borrador; si otra persona
cambia campos editables, se conserva la revisión anterior para evitar sobrescribirla.

La migración local específica `2026_11_19_180000_add_reused_mold_to_production_progress.php`
añade `reuses_mold` con valor inicial falso. Elegir reutilizar un molde permite cerrar
Moldes y ploteo sin compra nueva, mantiene el estado al sincronizar compras y queda
registrado en el historial. No permite omitir compras en otras etapas.

## Catálogo fijo y otros gastos

Desde la normalización, `production_stages` contiene solamente siete secciones compartidas,
con código, nombre, slug, orden, `show_in_stepper` y `delivery_tracking_default`.
Las seis etapas de fabricación aparecen en el stepper. «Otros» (`slug=otros`) no aparece
en el stepper ni tiene estado de avance; un botón compacto en el encabezado del stepper
muestra su cantidad de compras y abre la sección. El importe se consulta en su contenido.
Permite estibadores, transporte, carga, descarga y otros costos. Sus compras cuentan en el
gasto total y costo por unidad, y pueden activar seguimiento de entrega opcional.

`production_stage_progress` conserva el seguimiento particular de cada producción. Las
producciones nuevas no generan seis filas automáticamente: el registro se crea cuando se
guarda un avance. Los registros anteriores y sus historiales fueron conservados.
`purchases.production_stage_id` ahora apunta al catálogo compartido. Los eventos anteriores
mantienen sus IDs y su columna histórica `production_stage_id`, que referencia el progreso.

La navegación usa slugs, por ejemplo `purchases?stage=otros` y `purchases?stage=costura`.
Los enlaces anteriores con IDs de progreso se resuelven al abrir la sección correspondiente.

## Estado automático según compras

Desde el 14/11/2026, una etapa sin compras se muestra `pending`; con al menos una compra
se muestra `in_progress`, salvo que haya sido completada manualmente desde el stepper.
El botón fijo de cierre se retiró, incluido su posicionamiento y observadores de tamaño.
El menú de estado permite completar y reabrir; Pendiente y En proceso se calculan según compras.

La misma regla se aplica al resumen y al dashboard del CEO. Se calcula al consultar, sin
crear registros de progreso para etapas sin cambios manuales. Crear, editar, eliminar,
clasificar o reasignar compras sincroniza los progresos existentes dentro de transacciones.
Eliminar o mover la última compra deja la etapa pendiente, limpia `completed_at` y conserva
el cierre anterior en el historial; añadir otra compra después la inicia nuevamente, sin
recuperar automáticamente un cierre anterior. Otros gastos conserva estado nulo.

## Referencia histórica

La tarjeta de seguimiento general por etapa se retiró de la interfaz a petición del usuario;
el timeline de cada compra permanece debajo de sus conceptos.

Migración local aplicada:

```sh
php artisan migrate --path=database/migrations/2026_11_13_120000_normalize_production_sections.php --force
```

El respaldo y la verificación de compras, progreso e historial están en
`storage/app/private/backups/production-sections-*/`. La migración reanuda el ajuste final
de índices si MySQL interrumpe esa fase, sin volver a convertir las asignaciones de compras.
La reversión también conserva el historial y está cubierta por pruebas.

Las secciones siguientes documentan la implementación original anterior a esta normalización.

Cada manufactura con `type=production` tiene sus propias seis etapas, en este orden:
Moldes y ploteo, Muestras, Telas e insumos, Corte, Costura y Acabados.
No se crea una etapa de Empaque. Las órdenes de compra (`type=order`) conservan su flujo.

## Datos y reglas

- `production_stages` guarda estado, responsable, fecha comprometida, término real,
  observaciones y revisión de muestras. `created_at` y `updated_at` son las últimas columnas.
- `production_stage_events` conserva el actor, fecha y valores anteriores/nuevos de cada cambio.
- `purchases.production_stage_id` asocia cada compra completa a una etapa de su misma producción.
  Una compra mixta debe separarse en registros por etapa. Los conceptos siguen en `purchase_items`.
- Los estados son `pending` (gris), `in_progress` (amarillo), `completed` (verde)
  y `not_applicable` (gris, con motivo obligatorio).
- Registrar o clasificar compras no cambia el estado de una etapa. Puede haber varias en proceso.
- Completar una etapa requiere al menos una compra asignada a esa misma etapa y producción.
  El servidor valida esta condición dentro de la transacción de cambio de estado; las compras
  de otras etapas, otras producciones o sin clasificar no la cumplen.
- Cambiar una fecha ya comprometida exige un motivo. Los cambios se conservan en el historial.
- La revisión usa `revision` para rechazar ediciones concurrentes obsoletas, conservando el borrador.
- Las muestras requieren cantidad y aprobación para finalizar. Se registra quién aprueba y cuándo.
  Las unidades de muestra no crean variantes de producción, stock ni movimientos de Kardex.
- Las fotos de muestras se registran inicialmente mediante enlaces HTTP/HTTPS, hasta diez.
- Los nuevos registros de compras de una producción requieren una etapa válida. Editar compras
  históricas sin etapa conserva su condición hasta clasificarlas explícitamente.
- Los subtotales nuevos y editados se calculan en el servidor como cantidad por precio, con dos decimales.

## Interfaz

El stepper aparece en el detalle de cada producción; abre la sección Compras en la etapa elegida.
Allí se consultan seguimiento, historial, gastos por etapa y clasificación de compras anteriores.
El dashboard del CEO muestra las mismas etapas, muestras, responsables, fechas y alertas de atraso.
La recepción de prendas se muestra aparte como cantidades; el stepper no utiliza porcentajes.

## Migración local y despliegue

Se aplicó únicamente:

```sh
php artisan migrate --path=database/migrations/2026_11_10_100000_create_production_stages.php --force
```

La migración crea seis etapas pendientes por cada producción existente y deja las compras anteriores
sin clasificar. No intenta deducir su etapa, no cambia importes ni modifica inventarios.
La creación posterior de producciones genera sus etapas dentro de la transacción de creación.

Respaldo local: `storage/app/private/backups/production-stages-20261110-163059/`.
`verification.json` confirma que los datos previos de 31 manufacturas, 100 compras y 160 conceptos
no cambiaron. Se crearon 60 etapas para las diez producciones existentes.
La migración de Shopify permanece pendiente y no se ejecutó ningún proceso en hosting.

En hosting, desplegar código y respaldar la base antes de ejecutar esta migración específica;
no ejecutar todas las migraciones pendientes indiscriminadamente. El respaldo local es previo
a esta migración y no sustituye un respaldo actualizado del hosting.

## Clasificación de compras por descripción

La pantalla de compras muestra el importe y la cantidad de compras por grupo, el total de
producción y los registros pendientes. «Clasificar por descripción» procesa únicamente compras
sin etapa; conserva las asignaciones manuales, los importes y los estados de seguimiento.
Las coincidencias usan los conceptos de compra, ignoran mayúsculas y acentos, dan prioridad
a las muestras y distinguen servicios de los materiales mencionados en su descripción.
Si hay varios conceptos, se exige más del 60 % del importe reconocido para el grupo ganador.
Las compras sin una coincidencia clara se asignan a Otros. Después pueden reasignarse
mediante el buscador y el selector manual; las asignaciones existentes se conservan.

Para revisar o aplicar la clasificación desde consola:

```sh
php artisan productions:classify-purchases
php artisan productions:classify-purchases --apply
php artisan productions:classify-purchases --production=24 --apply
```

El 11/11/2026 se clasificaron 81 compras locales y quedaron 18 pendientes de revisión.
Repetir la operación no modifica las compras ya clasificadas.

El 13/11/2026 se incorporó Otros como destino cuando no existe una coincidencia clara
y se asignaron las 18 compras locales pendientes a ese grupo. No quedaron compras sin grupo.

## Pruebas

El modal de ingresos muestra una tabla de cuatro columnas: producto/variante,
cantidad, motivo y estado. La búsqueda por SKU, talla o color oculta filas sin
destruir sus formularios ni excluir sus cantidades seleccionadas. El visor usa
thumbnail y large para el zoom; las variantes sin foto usan un icono neutro.
El encabezado de fila conserva el nombre completo del producto y el valor de su
opción color, por ejemplo Blusa Barquillo FRESA. La opción se identifica por sus
metadatos, sin inferir tonos ni analizar palabras del nombre. El color no se
repite en una etiqueta; las tallas y demás opciones permanecen como información
secundaria. La búsqueda permite consultar ese nombre completo.
Las filas con cantidad válida se incluyen automáticamente, sin botones de check.

El total y el registro aparecen en un pie visible. Mientras se registra, el
formulario bloquea cambios y el cierre del modal de producción. La petición se
envía una sola vez; los errores quedan en el formulario y conservan el borrador.
Recepcion/Correccion se presentan con tildes sin cambiar sus valores de la API.
El selector compartido también conserva Fallado y Reparado en sus contextos.

La recepción usa el listado de kardex agrupado por fecha y dirección. La tabla y
el resumen Angular normalizan esos grupos mediante flattenKardexes, compartido
con los listados planos y el formato anterior date/items. Se suman movimientos
individuales, nunca los totales de sus contenedores. Las cantidades numéricas en
texto se convierten antes de sumar; entradas y salidas conservan su signo.
Esto evita filas vacías y NaN al abrir recepción o añadir movimientos nuevos.

Al registrar una compra desde una producción, el formulario recibe las secciones
ya cargadas y muestra un selector obligatorio antes de los conceptos. La sección
actual se preselecciona y puede corregirse, incluyendo Otros gastos. Se envía el
production_stage_id elegido y se confirma el destino al cerrar el formulario.
La compra aparece únicamente en su sección; los totales de la producción y los
estados de las etapas se actualizan con el flujo existente.

Cambiar de sección ajusta la sugerencia de seguimiento de entrega únicamente si
el usuario todavía no ha modificado ese bloque. Los conceptos, proveedor, fechas
y elecciones manuales se conservan. Los formularios de otros módulos mantienen
su comportamiento sin selector. No se cambia la estructura de la base de datos.

Pruebas de Laravel: `ProductionStagesTest` y `AreaDashboardTest` (SQLite aislado).
Pruebas Angular: etapas, compras y gastos, dashboard por áreas y órdenes de compra.

## Archivos de la etapa

La tabla polimórfica `attachments` usa `attachable_type` y `attachable_id`.
El modelo `Attachment` declara `morphTo('attachable')` y
`ProductionStageProgress::attachments()` declara `morphMany`. El alias estable
`production_stage_progress` se registra en el mapa de relaciones.
La migración `2026_11_20_140000_convert_stage_attachments_to_polymorphic.php`
copia y verifica los registros originales antes de retirar
`production_stage_attachments`. Conserva los IDs, rutas, nombres, usuarios,
tamaños y fechas; no mueve ni elimina los archivos físicos. La reversión se
rechaza si existen adjuntos de otros tipos para evitar perderlos.

Los archivos se guardan en el disco privado local. GET y POST
`/stages/{stage_id}/attachments` listan y suben; GET
`/stages/{stage_id}/attachments/{attachment_id}` descarga tras validar tienda,
producción y etapa mediante la relación del propietario. El cliente no elige
el tipo ni el ID polimórfico. Se valida contenido, extensión y un máximo de 10 MB.
La interfaz permite adjuntar y descargar desde Archivos en la cabecera; durante la
subida se bloquea el cambio de pestaña o etapa para conservar el contexto.

El bloque Clasificar o reasignar compras solo aparece si hay compras sin clasificar.
Los mensajes de resultado permanecen visibles después de completar la clasificación.

La vista de compras de una etapa utiliza `compactHeader`: oculta el título y las
instrucciones repetidas, conserva total y registros, y muestra Registrar compra.
Las tarjetas usan bordes neutros sin franja violeta. El contenido reserva espacio
inferior para que el botón fijo no impida alcanzar las últimas filas.

La cabecera y el contenido comparten un card padre. Las pestañas tienen bordes
superiores redondeados y la activa comparte el fondo del panel, que contiene
las tarjetas individuales de compras. Los paneles mantienen su estado al cambiar
de pestaña y el botón fijo de cierre permanece fuera del contenedor.

## Fotos y adjuntos de cada compra

El menú de cada compra incorpora Archivos adjuntos. Abre el gestor compartido
de adjuntos para subir archivos (hasta 10 MB), descargar y previsualizar imágenes
JPEG, PNG, WebP y GIF. La carga de archivos es diferida hasta abrir el menú.
El cierre del modal se bloquea mientras se sube un archivo.

Purchase declara la relación polimórfica attachments; se reutiliza la tabla
existente con el tipo App\Models\Purchase, sin cambiar el mapa de relaciones
que usan los pagos e imágenes anteriores. El servicio AttachmentFileService
comparte almacenamiento y validación con los adjuntos de etapas.

GET/POST `/purchases/{purchase_id}/attachments` listan y suben; GET
`/purchases/{purchase_id}/attachments/{attachment_id}` descarga y GET
`/purchases/{purchase_id}/attachments/{attachment_id}/preview` sirve una imagen
tras validar la tienda y el propietario. La vista previa rechaza los formatos
que no sean imágenes permitidas y no expone rutas públicas.

## Galería y contadores de adjuntos

La galería acepta imágenes, PDF, Word, PowerPoint, Excel y los demás formatos
permitidos (máximo 10 MB por archivo). Las imágenes muestran miniaturas privadas
al abrir el gestor, con un máximo de tres solicitudes simultáneas. Los documentos
muestran un icono según su formato; no se solicita vista previa de documentos.
Las URLs temporales de las imágenes se liberan al cerrar o cambiar de compra.

El listado de compras de producción obtiene `attachments_count` mediante
`withCount('attachments')`. El contador de comprobantes suma las imágenes de
los pagos cargados mediante `payments.images`, no el número de pagos.
Los contadores aparecen separados en el pie de cada tarjeta, incluidos los ceros.
`filesChange` y `paymentsChange` actualizan la tarjeta al guardar archivos o
comprobantes. Los cambios de imágenes persistidos desde el formulario de un pago
se propagan sin guardar ni cerrar el editor del pago.

Recepción de mercadería es una excepción al conjunto de pestañas: abre
directamente el bloque existente de recepciones, ingresos, salidas y su timeline.
No instancia Compras, Seguimiento ni Historial adicional. Los enlaces antiguos
con otras vistas también abren Recepciones. Esta etapa se excluye de los selectores
de compras y reasignación; las demás etapas siguen abriendo Compras por defecto.

En el listado de producciones, tanto el nombre como Abrir panel enlazan a
`/{production_id}/overview`. El menú conserva los accesos específicos a variantes
y recepciones. En escritorio se muestran columnas de cantidades, costos y
actualización; en móvil las filas se presentan como tarjetas con etiquetas de
campo, conservando filtros, importes y acciones. Las unidades recibidas usan
un tono neutro para cero, verde para cantidades y ámbar si exceden lo planificado.
