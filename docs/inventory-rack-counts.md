# Conteos por zona y fila del rack

En Conteos, el almacén queda fijado por la sesión. El usuario selecciona una zona y después su sububicación. Si hay más niveles, aparece un selector por nivel hasta llegar a una ubicación final válida.

El escáner recibe únicamente el `location_id` final. Elegir una zona con hijos no habilita la captura. La ruta completa, por ejemplo `ZONA A / FILA 3`, permanece visible durante el conteo. «Finalizar captura» limpia la selección y permite comenzar en otra fila; mientras se guarda una captura no se puede cambiar de ubicación.

La API de opciones incorpora `location_tree` con la jerarquía activa dentro del ámbito del inventario. Mantiene `locations` como lista de ubicaciones finales para compatibilidad. El backend sigue validando tienda, ámbito del inventario, almacén de la sesión y ubicación final al guardar.

## Configuración local

En `sorelle_production`, Tienda 422 tiene las zonas A–F y cinco sububicaciones por zona: `SUPERIOR`, `FILA 1`, `FILA 2`, `FILA 3` y `FILA 4`. Se conservaron los seis padres existentes y se insertaron los 30 hijos, con los identificadores y códigos proporcionados. Todos los hijos tienen `is_active = 1`; los valores 2–6 del SQL recibido se normalizaron porque ese campo es booleano.

Se comprobó previamente que las zonas no tuvieran conteos ni avances registrados, para no convertir en agrupadores ubicaciones con historial de conteo. La verificación posterior confirmó las 30 ubicaciones finales y la conservación de todos los registros anteriores de las 77 tablas.

Respaldo y exportación completa actualizados: `storage/app/private/backups/rack-sublocations-20261107-135149/`. Contiene `before.sql`, el plan de inserción, las huellas de integridad y `sorelle_production.sql.gz`. El hosting no fue modificado.

## Verificación

Pruebas Laravel con SQLite para jerarquía, aislamiento, selección de filas y rechazo de conteos en padres. Pruebas Angular para selección en cascada, bloqueo del escáner hasta llegar a la fila, ubicación enviada a la captura y reinicio seguro. También se verificó el selector compartido de almacenes.

## Rack virtual

La vista inicial de Conteos muestra un rack por zona del almacén de la sesión. Cada sububicación aparece como un nivel: SUPERIOR primero y las filas en orden numérico. Las tarjetas muestran producto, talla/color, SKU, imagen y cantidad vigente en esa ubicación. La ubicación seleccionada para capturar se destaca como «En captura».

El selector de zona permite recorrer el almacén sin cambiar la ubicación donde se están registrando los conteos. «Historial» conserva la tabla por variante y sus enlaces de edición.

`GET .../sessions/{session}/counts?view=rack&location_id={location}` devuelve productos agregados por variante, con páginas de 20 y un resumen completo de unidades, capturas y variantes de ese nivel. El resumen incluye todas las páginas; no suma cantidades anuladas ni reemplazadas. Una captura válida de cero permanece visible y se distingue de la ausencia de capturas.

El detalle de capturas se consulta bajo demanda por variante y ubicación, también paginado, e incluye sus estados y acceso a edición. Cambiar de zona cancela las solicitudes anteriores. Una actualización de los datos de la sesión refresca el rack.

Este cambio añade consultas y presentación. No modifica tablas ni registros. Verificado con pruebas Laravel de agregación, aislamiento y paginación; pruebas Angular de niveles físicos, suma por ubicación, ceros, navegación e historial; compilación completa y consulta de lectura sobre MySQL local.