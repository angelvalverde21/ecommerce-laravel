# Recepciones de mercadería

## Guardar, verificar e ingresar al inventario

Producciones y órdenes de fabricación comparten el mismo formulario y listado.

1. **Guardar recepción** guarda fecha, observaciones, responsable y detalle. No
   crea movimientos en kardex ni modifica existencias.
2. La recepción queda **Guardada · Pendiente de ingreso**. Puede reabrirse con
   **Editar recepción** y obtenerse su PDF mediante **Imprimir hoja**.
3. **Confirmar ingreso al inventario** muestra el total de unidades y variantes.
   La segunda confirmación genera entradas en kardex y aumenta `variants.stock`
   en una transacción. Después se muestra **Ingreso confirmado**.

El modal se dedica a guardar o editar. Después de guardar correctamente se cierra
y actualiza el listado que queda debajo, conservando la página al editar.
Si falla el guardado, permanece abierto con las cantidades ingresadas.
La impresión y la confirmación del ingreso están únicamente en las tarjetas del listado.
El PDF usa el documento guardado y muestra número, versión, origen, responsable,
fecha, variantes, cantidades, total y espacios para verificación y firmas.
Identifica claramente las hojas pendientes que todavía no afectan existencias.

La rueda del ratón no modifica los inputs de cantidades del selector compartido.
Las cantidades son unidades enteras positivas. Se permiten recepciones parciales.

## Persistencia

Cada producción u orden tiene su propio correlativo `number`, empezando en 1.
Se muestra como **Recepción N.º 1** en tarjetas, formularios, PDF y referencias
del kardex. El ID global se conserva exclusivamente para relaciones y rutas.
El número se asigna al guardar bajo bloqueo del documento de origen y tiene un
índice único por tienda/origen/número. Editar o revertir no cambia el correlativo.
Los documentos existentes se numeraron por fecha de recepción y registro.

Las hojas pendientes muestran **Confirmar ingreso al kardex**. Las ya registradas
muestran el control deshabilitado **Ingreso confirmado en kardex**, para dejar
visible su estado sin permitir un segundo ingreso.

- `inventory_receptions`: tienda, origen polimórfico, UUID de solicitud, hash del
  contenido, fecha, notas, responsable, estado, versión y auditoría.
- `inventory_reception_items`: variantes, cantidades, motivo y copia de nombre,
  SKU y opciones al guardar.
- `kardexes.inventory_reception_item_id`: renglón del documento que originó el
  movimiento. Permanece NULL mientras la recepción está guardada sin confirmar.
- `kardexes.reverses_kardex_id`: entrada original compensada por una reversión.

Estados: `draft` (guardada), `posted` (ingreso confirmado), `reversed` (revertida).
`revision` se incrementa al editar, confirmar o revertir. `posted_at` y
`posted_by` conservan la fecha y responsable de confirmación, por separado de la
fecha efectiva de recepción y de quién creó la hoja.

El kardex sigue vinculado a Manufacture mediante `kardexable`, conservando las
consultas existentes. El origen polimórfico de la recepción permite extenderla a
otros documentos; los endpoints actuales aceptan Manufacture de tipo production
u order de la tienda autorizada.

## Consistencia

Guardar valida tienda, vínculo de la variante con el documento y estado activo.
Confirmar vuelve a validar todas las variantes. Bloquea documento y variantes,
crea las entradas y actualiza stock en una misma transacción: si una línea falla
se conserva la hoja pendiente y no se aplica ninguna entrada.

El UUID evita duplicar hojas al reintentar el guardado. La revisión evita editar
o confirmar una copia desactualizada. Confirmar una recepción ya ingresada no
genera movimientos ni stock adicionales. La UI vuelve a cargar el kardex del
servidor para no duplicar filas al recibir una respuesta de reintento.

Solo se editan documentos draft. Los confirmados ofrecen **Revertir recepción
completa**, con el total afectado y motivo obligatorio. La reversión conserva la
recepción y sus entradas, crea salidas compensatorias y registra fecha y usuario.
Las recepciones nativas descuentan el stock que habían ingresado; se rechaza toda
la reversión si no hay stock suficiente. Los reintentos no duplican salidas.

## API

El historial se consulta bajo demanda mediante `KardexHistoryComponent`: inicia
cerrado sin pedir movimientos, carga al desplegar y conserva el resultado al
cerrar y abrir de nuevo. Una operación de inventario invalida esa copia; si el
acordeón está abierto se actualiza, si está cerrado espera a la próxima apertura.
Incluye actualización manual y reintento de consulta sin bloquear las recepciones.

Las fichas generales usan `include_kardex=0` para evitar la carga indirecta de
movimientos dentro de variantes. El selector del modal usa la misma opción en
el endpoint de variantes. Los totales se actualizan con `summary_only=1`, que
devuelve agregados sin hidratar variantes, pagos ni movimientos. Los clientes que
no envían esas opciones conservan la respuesta detallada anterior.

Base: `/api/v1/{store}/dashboard/manufactures/{id}/receptions`.

| Método | Ruta | Operación |
|---|---|---|
| GET | / | Listado paginado |
| POST | / | Guardar nueva hoja pendiente |
| PUT | /{reception} | Editar hoja pendiente, exige revision |
| POST | /{reception}/post | Confirmar ingreso, exige revision |
| GET | /{reception}/print | PDF de la hoja guardada |
| POST | /{reception}/reverse | Revertir recepción completa con reason |

Guardar recibe request_id, received_on, notes e items. Cada ítem contiene
variant_id, quantity y comment (Recepcion o Correccion).

## Recepciones históricas

`inventory:backfill-receptions --dry-run` analiza; `--store=1` limita la tienda.
El importador agrupa entradas por tienda, origen, motivo y fecha/hora exacta.
Vincula movimientos existentes, sin crearlos ni modificar stock. Excluye salidas
y reingresos Reparado. Repetir el comando no procesa entradas ya vinculadas.

Los documentos reconstruidos tienen is_historical=true, stock_applied=false y
status=posted. El responsable desconocido queda NULL. Los nombres disponibles
proceden del catálogo actual; si falta un producto se conserva su identificador
sin inventar datos. Los lotes incompletos se reportan sin modificarlos.

La reversión histórica compensa exclusivamente el kardex; como la reconstrucción
no aplicó stock, tampoco lo descuenta. La UI lo informa antes de confirmar. Si
falta una variante, se rechaza la reversión.

Reconstrucción local: 64 documentos (54 órdenes y 10 producciones), 263 entradas
y 2391 unidades. Stock y datos originales del kardex permanecieron iguales.
Las 42 salidas anteriores se conservaron. Estos documentos no vuelven a ingresar
al inventario al habilitar el flujo de guardado y confirmación.

## Migraciones y pruebas

Migraciones, en orden:
- 2026_11_18_120000_create_inventory_receptions.php
- 2026_11_18_130000_add_historical_inventory_receptions.php
- 2026_11_18_140000_add_inventory_reception_posting.php
- 2026_11_18_150000_number_inventory_receptions.php

La tercera conserva los documentos existentes como posted/reversed y agrega
la revisión y la auditoría de confirmación sin generar movimientos.

InventoryReceptionsTest usa SQLite aislado para guardado, edición, PDF, ausencia
de efectos antes de confirmar, concurrencia por revisión, confirmación idempotente,
rollback, órdenes, aislamiento por tienda, historial y reversión.
La configuración Angular production-module incluye el flujo, reapertura de hojas,
bloqueo de rueda, revisión final y protección frente a confirmaciones repetidas.
