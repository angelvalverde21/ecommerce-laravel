# Interfaz de pagos

El listado y los formularios compartidos de pagos conservan los endpoints y las
relaciones polimórficas existentes. No requieren cambios en la base de datos.

## Componentes

- `PaymentFormComponent`: monto, medio, fecha, dirección, observación y comprobantes.
- `PaymentCreateComponent` y `PaymentEditComponent`: formularios con validación,
  error en línea, acciones al pie y bloqueo mientras se guarda.
- `PaymentIndexComponent`: listado, creación, balance y emisión de `paymentsChange`.
- `PaymentIndexRowComponent`: fecha, concepto, dirección, medio, miniatura,
  importe y acciones de edición/eliminación.
- `payment-editor.model.ts`: construcción del formulario y envío multipart al crear.
- `_payment-editor.scss` y `_payment-ui.scss`: estilos compartidos del editor,
  cabeceras de modal y listado.

El modal usa cabecera con icono y descripción, monto destacado, selección visual
de ingreso/salida, comprobantes desplegables y acciones de 44 px. El listado se
presenta como tabla en escritorio y fichas por movimiento en móvil.

## Comportamiento

El registro envía FormData con los archivos como `images[]`. La edición conserva
el endpoint JSON y gestiona sus imágenes con `ImageIndexComponent`, usando
`imagesChange` para actualizar la colección sin duplicar miniaturas.
Las vistas previas usan `ImagePreviewComponent`: miniatura en la tabla y large
para ampliar. Se mantiene el límite del backend: imágenes de hasta 2 MB.

La fecha inicial usa el día local, evitando el desfase del día UTC. El importe
debe ser positivo con hasta dos decimales y no cambia con la rueda del ratón.
La dirección conserva los valores `in` y `out`; el método conserva `gateway_id`.

El formulario se bloquea durante la petición, impide envíos repetidos mientras
está pendiente o después de recibir éxito, y se recupera ante un error. El modal
cierra al recibir el pago guardado. Si falla, los campos y archivos se mantienen.

Crear, editar y eliminar recalculan el balance del listado y emiten la colección
actualizada. El balance conserva la convención anterior: ingresos positivos y
salidas negativas. En la orden de fabricación, `paymentsChange` actualiza también
el número de pagos y su suma en el resumen, con la misma convención que el
agregado financiero existente del servidor.

`GatewaySelectedComponent` admite altura y margen mediante las variables CSS
`--gateway-select-min-height` y `--gateway-select-margin-bottom`; el formulario
de pagos usa 44 px y elimina el margen interno redundante.

## Verificación

`payment-workflow.spec.ts` forma parte de la configuración Angular
`production-module`. Verifica validación, archivo multipart, bloqueo de envíos,
recuperación tras errores, cancelación, bloqueo de rueda, edición por ID,
recalculo de totales y registros sin imágenes.
