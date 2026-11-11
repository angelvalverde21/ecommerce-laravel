# Etapas de producción

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

## Verificación

Pruebas de Laravel: `ProductionStagesTest` y `AreaDashboardTest` (SQLite aislado).
Pruebas Angular: etapas, compras y gastos, dashboard por áreas y órdenes de compra.
