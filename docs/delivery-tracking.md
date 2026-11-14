# Seguimiento de entregas

Cada compra puede tener un seguimiento independiente de sus pagos. `delivery_trackings`
usa una relación polimórfica única por propietario y `delivery_tracking_events` conserva
el historial. El trait `HasDeliveryTracking` permite habilitar otros modelos; el controlador
expone únicamente los tipos admitidos en su registro `OWNERS` y valida la tienda y su usuario.

## Fechas e historial

- `handed_over_on`: fecha real del encargo o entrega de materiales; opcional.
- `expected_on`: compromiso vigente; obligatorio al activar el seguimiento.
- `received_on`: recepción real; opcional hasta recibir el encargo.
- `revision`: rechaza escrituras concurrentes obsoletas.

Crear o actualizar fechas y registrar el evento se realiza en una transacción. El evento
guarda usuario, nombre del usuario en ese momento, fecha y hora, valores anteriores/nuevos
y motivo. Las reprogramaciones y correcciones de fechas ya registradas exigen un motivo.
Las fechas reales no pueden ser futuras ni anteriores al encargo. Una entrega recibida
conserva la fecha comprometida. Corregir su recepción requiere motivo y queda auditado.

Los estados se calculan: `pending`, `overdue` o `received`. La fecha vigente se compara
con el día de Lima; una entrega prevista para hoy no está vencida. `received_late` señala
recepciones posteriores al último compromiso. No se alteran pagos, importes, stock,
aprobaciones de muestras ni el estado de la etapa al recibir una compra.

## API

Prefijo: `/api/v1/{store}/dashboard`.

- `POST /delivery-trackings`: tipo permitido (`purchase`), ID y fechas.
- `GET /delivery-trackings/{id}`: situación vigente y cantidad de reprogramaciones.
- `PUT /delivery-trackings/{id}`: fechas modificadas, revisión y motivo cuando corresponda.
- `GET /delivery-trackings/{id}/events?page=1`: historial descendente, 20 eventos por página.

Crear una compra acepta `delivery: { enabled, handed_over_on, expected_on }`. Si se habilita,
la compra, conceptos y seguimiento se guardan juntos o se revierten juntos. Las ediciones
posteriores del seguimiento usan su endpoint auditado; editar los conceptos no lo reemplaza.
No existe endpoint para eliminar el historial ni para desactivar un seguimiento guardado.

## Interfaz

Muestras y Costura activan inicialmente la opción en el formulario de nueva compra.
El usuario puede desmarcarla. Las demás etapas permiten activarla manualmente.
Las compras anteriores muestran «Agregar seguimiento»; no se inventan fechas retrospectivas.
`purchase_start` y `purchase_end` se conservan como campos anteriores y no alimentan el timeline.

La tarjeta muestra estado y fechas; el historial se carga al desplegarlo. Componentes Angular:
`DeliverySetupComponent`, `DeliveryTrackingComponent` y `DeliveryTimelineComponent`.
El stepper y las compras encabezan la página; el resumen operativo aparece al final.

El dashboard del CEO incorpora `delivery_overview`: pendientes, vencidas, entregas de hoy,
los ocho compromisos más próximos y hasta ocho compras reprogramadas en los últimos siete días.
Incluye seguimiento de otras etapas y compromisos anteriores al período del dashboard.
Los accesos llevan a la etapa de la compra. Su actualización sigue el refresco existente de 60 s.

## Instalación y validación

Aplicar exclusivamente la migración del módulo:

```sh
php artisan migrate --path=database/migrations/2026_11_13_100000_create_delivery_tracking_tables.php --force
```

Se aplicó localmente sin modificar compras anteriores. No se ejecutaron otras migraciones.
La vista de diseño contiene datos ilustrativos aislados de la base del ERP.
Pruebas: `DeliveryTrackingTest`, `ProductionStagesTest`, `AreaDashboardTest` y las suites
Angular de entregas, etapas, compras y dashboard.

```sh
php artisan test --filter='DeliveryTrackingTest|ProductionStagesTest|AreaDashboardTest'
```

Desde el proyecto Angular:

```sh
npx ng test --configuration production-module
```

La configuración usa los alias del proyecto y compila exclusivamente las suites del módulo.
El componente de entrega recibe `ownerType` y `ownerId`; habilitar otro propietario requiere
el trait en el modelo y su incorporación explícita al registro de tipos admitidos del servidor.

La primera versión controla una recepción completa por compra. Entregas parciales necesitan
un modelo de recepciones con cantidades, en lugar de reutilizar eventos de reprogramación.
