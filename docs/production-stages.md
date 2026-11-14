# Etapas de producción

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

Pruebas de Laravel: `ProductionStagesTest` y `AreaDashboardTest` (SQLite aislado).
Pruebas Angular: etapas, compras y gastos, dashboard por áreas y órdenes de compra.
