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

## Rack virtual y capturas individuales

La vista inicial organiza las capturas por zona y sububicación del almacén de la sesión. SUPERIOR aparece primero y las filas siguen un orden numérico. Cada captura ocupa una fila propia, aunque se repita el producto o la variante. La fila muestra imagen, producto, talla/color, cantidad, ronda, estado, responsable, fecha y enlace de edición.

Los registros no se agrupan ni se suman por variante en la tabla. El mismo criterio se aplica a la vista Historial. Los totales del rack y de cada nivel suman únicamente capturas vigentes; los registros anulados o reemplazados permanecen visibles con su estado y la cantidad tachada.

`GET .../sessions/{session}/counts?view=rack&location_id={location}` devuelve páginas de 20 capturas individuales, ordenadas desde la fecha más reciente y por identificador descendente cuando coinciden. `summary` conserva las unidades, capturas y variantes vigentes de toda la ubicación, incluyendo las páginas aún no cargadas. `total` cuenta todos los registros de la ubicación para paginar correctamente.

El selector de zona no modifica la ubicación seleccionada para registrar cantidades. Cambiar de zona cancela las solicitudes anteriores. Una actualización de los datos de la sesión refresca el rack. Los conteos en cero permanecen visibles y se distinguen de las ubicaciones sin capturas.

Este cambio modifica presentación y consultas, sin alterar tablas ni registros. Las pruebas verifican filas separadas para capturas repetidas, paginación del mismo producto, conservación de ceros, exclusión de anulaciones de los totales y aislamiento por sesión y empresa.

## Advertencia de cantidad elevada

Únicamente al intentar guardar una captura vigente con más de 50 unidades, el formulario muestra una advertencia por posible código de barras y solicita confirmación antes de enviar el registro. No muestra avisos mientras se escribe. Aplica también a la edición. La opción «Revisar cantidad» conserva el valor, no envía la captura y devuelve el foco al campo de cantidad. El diálogo enfoca esa opción por defecto para evitar una confirmación accidental con el lector.

El umbral se aplica a la cantidad de esa captura, no al total acumulado del producto. Exactamente 50 unidades no requieren confirmación. Anular una captura tampoco la requiere. Es una protección de la interfaz; no establece un límite nuevo en la base de datos.
## Racks y tarjetas por nivel

Las seis ubicaciones superiores de Tienda 422 se renombraron de ZONA A–F a Rack A–F, con códigos `rack_a`–`rack_f`. Se actualizaron únicamente nombre, código y fecha de modificación de los seis registros. Sus IDs, relaciones de las filas y las 72 capturas existentes permanecieron intactos; se verificaron las huellas de las 77 tablas.

El respaldo previo y la exportación completa posterior están en `storage/app/private/backups/rename-racks-20261109-124549/`. Incluye `before.sql`, `rename-plan.json`, `baseline.json`, `verification.json` y `sorelle_production.sql.gz`. Solo se modificó la base local.

SUPERIOR y FILA 1–4 se presentan en tarjetas independientes, apiladas verticalmente. Cada tarjeta conserva su tabla de capturas individuales. «Contar aquí» permite seleccionar directamente la fila cuando no existe otra captura activa; valida que la ubicación pertenezca al almacén y al ámbito permitido, y mantiene la ubicación fijada hasta finalizar la captura actual.
## Eliminación de capturas para corregir errores

El rack y el historial incluyen una acción de eliminación para capturas propias, vigentes y de sesiones abiertas. Se confirma indicando el ID, producto y cantidad. Cancelar el diálogo no envía ninguna solicitud. Los botones quedan bloqueados durante la operación y los errores conservan la fila para reintentar.

`DELETE .../sessions/{session}/counts/{count}` elimina físicamente una captura después de validar empresa, inventario, sesión, estado y autor. La transacción utiliza el mismo bloqueo del inventario que el registro y la edición. Se rechaza si el inventario está finalizado/cancelado o tiene ajustes aprobados/aplicados. No modifica stock ni Kardex.

Después se recalculan los totales mediante las consultas del listado y se invalida la verificación de esa ubicación. Si quedan capturas vigentes en cualquiera de sus sesiones, vuelve a «En progreso»; si ya no queda ninguna, pasa a «Pendiente», sin fechas de conteo/verificación. Un fallo de actualización del avance revierte también la eliminación.

La interfaz recarga el rack o la página del historial, retrocede una página si se eliminó su último registro y actualiza el total del producto abierto sin borrar la cantidad que se esté ingresando. No se ejecutó ninguna eliminación sobre los datos locales al implementar esta función; los casos de borrado se probaron con SQLite aislado.
## Carga por acordeón

Los niveles SUPERIOR y FILA 1–4 empiezan cerrados. No se consultan capturas hasta que el usuario despliega el nivel. La primera apertura solicita su página inicial; cerrar y reabrir conserva los datos y las páginas ya cargadas sin repetir la consulta.

Actualizar el rack o recibir nuevos datos de la sesión invalida las cachés y recarga únicamente los niveles que sigan abiertos. Los cerrados esperan hasta su siguiente apertura. Cambiar de rack cancela las consultas pendientes y presenta sus niveles cerrados. Los cambios de permisos, indicadores de eliminación o botones no reinician los datos del acordeón.

Los encabezados muestran el total de unidades y el número de capturas vigentes incluso cerrados. Una consulta ligera `view=rack-summary` agrega cantidades por ubicación dentro de la sesión, excluye capturas anuladas y devuelve únicamente los totales. El detalle de capturas sigue cargándose solo al desplegar el nivel. La paginación no limita estas sumas. Si falla el resumen, se muestra un error con reintento, sin presentar ceros como datos conocidos.

## Inicio de captura desde el rack

El escáner y la cantidad permanecen ocultos hasta pulsar «Contar aquí». El formulario aparece con la ubicación bloqueada, recibe el foco en el código de barras y se oculta con «Finalizar captura». Para cambiar de ubicación se termina primero la captura activa.

Cada ubicación final ofrece su propio botón: una fila, una sububicación más profunda o un rack/zona completo cuando no tiene subdivisiones. Se conserva la validación de empresa, ámbito del inventario y almacén de la sesión. Los nodos con hijos requieren elegir su ubicación final para evitar conteos ambiguos. No se modificaron datos ni esquema de la base para este cambio.

## Nombres de estantes

Las ubicaciones 1–6 de la empresa 1 y almacén 4 ahora se llaman Estante A–F y utilizan los códigos estante_a–estante_f. Se conservaron IDs, relaciones, filas y capturas. Respaldo SQL completo y exportación actualizada: storage/app/private/backups/rename-estantes-20261109-155949/. Se verificaron 77 tablas y se conservaron las 71 capturas existentes al ejecutar el cambio. El hosting no se modificó.

El título principal de la vista muestra el nombre de la ubicación seleccionada y se actualiza al cambiar de estante. La pestaña se llama «Estantes».

## Zonas sin subdivisiones

Cuando la ubicación seleccionada es una zona final sin hijos, «Contar aquí» aparece en el encabezado principal y emite el ID de la propia zona. El resumen indica «Conteo directo en esta zona · Sin subdivisiones»; el acordeón inferior se titula «Capturas de esta zona» y consulta sus registros solo al abrirse. No se representa la zona como una fila ni se exige seleccionar otra ubicación. Las ubicaciones con hijos mantienen los botones en sus niveles finales. La prueba del flujo completo verifica que el POST conserve el location_id de la zona. No se modificaron la estructura ni los datos de la base.

## Formulario dentro de la ubicación

El formulario de código de barras y cantidad se declara una sola vez y se presenta con NgTemplateOutlet dentro de la ubicación activa. Si es una zona final con parent_id null, aparece en el bloque de esa zona. Si tiene ascendientes, aparece dentro de la tarjeta de la ubicación final más anidada, con su ruta completa. No aparece sobre el listado ni en los padres de la fila elegida.

El formulario permanece fuera del panel plegable de capturas para conservar el producto y la cantidad al cerrarlo. Se mantiene la misma instancia al actualizar resúmenes y capturas o fallar una actualización. El selector de estante y las pestañas se bloquean hasta «Finalizar captura» para no descartar una captura pendiente. Se conserva el foco de escaneo, la confirmación de cantidades mayores de 50 y la actualización del total de la variante al corregir capturas. Las pruebas verifican el guardado en la zona raíz, la ubicación en tres niveles y la conservación del formulario.

No se modificaron tablas ni registros de la base de datos para este ajuste.

## Registros visibles durante la captura

Al iniciar un conteo, el acordeón de la ubicación activa se abre y consulta automáticamente sus capturas. En ubicaciones anidadas, la tabla aparece justo debajo del formulario dentro de la misma tarjeta; en zonas raíz sin hijos, aparece debajo del bloque de captura de la zona. Las filas conservan cantidad, opciones, responsable y acciones individuales. Se muestra un encabezado con las unidades vigentes y el número de capturas de esa ubicación.

Guardar vuelve a consultar el listado y los totales, abre el panel activo si estaba plegado y muestra las capturas más recientes primero, sin reemplazar la instancia del formulario. El resto de niveles mantiene la carga al desplegar, con 20 registros por página. No se modificaron datos ni esquema de la base.

## Apertura y cierre sin recarga general

«Contar aquí» y «Finalizar captura» solo actualizan la ubicación activa; no vuelven a consultar inventario, sesión ni opciones, ni reconstruyen los demás estantes. Al iniciar se abre la lista de la ubicación elegida; si ya está cargada se reutiliza. Finalizar conserva esa lista y sus páginas, y no realiza consultas.

Al guardar o eliminar desde la vista de estantes, se actualiza únicamente la primera página y el resumen de la ubicación afectada. Durante la consulta se conservan los registros anteriores y las otras ubicaciones. Una eliminación confirmada quita inmediatamente su fila. Cada ubicación cancela sus propias consultas pendientes para impedir que una página antigua se mezcle con el resultado actualizado. Los resúmenes iniciales no sobrescriben un resumen obtenido con las capturas actuales. El botón general «Actualizar» sigue disponible para una recarga explícita y el historial conserva su flujo de actualización.

No se modificaron datos ni esquema de la base de datos.

Al finalizar la captura se cierra únicamente el acordeón de la ubicación que estaba activa, tanto en zonas raíz como en filas anidadas. Las otras ubicaciones conservan su estado y las capturas permanecen en caché; volver a iniciar abre el panel sin nuevas consultas si ya estaba cargado.

## Transición del acordeón

La apertura y el cierre utilizan una transición de altura natural mediante CSS Grid de 260 ms, con desvanecimiento y rotación de la flecha. Los registros se crean al abrir por primera vez y se conservan dentro del panel para permitir la animación de cierre y reutilizar los datos. Un panel cerrado tiene aria-hidden e inert para impedir el foco o las acciones sobre contenido oculto. La preferencia prefers-reduced-motion desactiva las transiciones. No se agregaron consultas ni dependencias de animación.

## Variante contada en otra ubicación

La consulta de variante conserva el endpoint de conteos existente y devuelve other_locations al recibir variant_id y location_id. La lista agrega únicamente capturas vigentes de esa variante en otras ubicaciones, dentro del mismo inventario y a través de todas sus sesiones, incluso cerradas. Excluye la ubicación actual, saldos de cero unidades, capturas anuladas o reemplazadas, otras tallas/variantes y otros inventarios. Cada resultado contiene la ruta completa de ubicación, empresa/sede, almacén y cantidad contada. No se ejecuta una segunda petición HTTP de búsqueda para obtener esta advertencia.

Antes de habilitar la cantidad se muestra una advertencia: «Mantener aquí» permite registrar las unidades nuevas en la ubicación actual; «Mover a ubicación anterior» indica dónde llevar físicamente las unidades y pide contarlas allí. Esta última opción limpia el producto de la captura actual y no guarda ni traslada conteos previos. Cuando hay varias ubicaciones anteriores, se permite elegir el destino. «Cancelar» vuelve al escáner sin registrar unidades. Una respuesta antigua no puede aplicar una decisión a otro producto o ubicación, y cerrar la captura cierra su diálogo pendiente.

La comparación usa la variante exacta para no confundir tallas o colores diferentes. Los nombres se muestran como texto y no se interpretan como HTML. La implementación no modifica tablas ni registros de la base local; las escrituras de las pruebas se realizaron en SQLite aislado.

### Compatibilidad con MySQL estricto

La agregación de otras ubicaciones usa InventoryCount con whereHas(session), evitando que la relación hasManyThrough añada laravel_through_key al SELECT agrupado. Esa columna adicional causaba el error 1055 en ONLY_FULL_GROUP_BY, aunque las pruebas en SQLite pasaban. Se reprodujo el fallo original en MySQL local y se verificó la respuesta corregida HTTP 200 para inventario 44, variante 84 y ubicación actual 35, con una ubicación adicional. La comprobación se ejecutó dentro de una transacción de solo lectura y restauró el modo SQL de la conexión. No se modificaron datos, tablas ni la configuración global del servidor.

## Inicio automático al mover a una ubicación anterior

Elegir «Mover a ubicación anterior» emite el destino, la variante escaneada y el total contado de referencia. La página valida el origen activo y que el destino sea una ubicación final disponible en el almacén de la sesión; después cierra el origen y abre automáticamente la captura del destino, en su zona principal o fila anidada.

El formulario del destino recibe la variante ya consultada, deja la cantidad vacía y enfoca ese campo. No vuelve a escanear ni repite la misma advertencia. Los registros de la ubicación de destino se abren debajo del formulario. La nueva captura se guarda solo después de ingresar y confirmar su cantidad; los conteos anteriores permanecen intactos. Si hay varios destinos habilitados, se elige uno antes de cambiar. Ubicaciones de otro almacén o no disponibles no se ofrecen como destino automático porque la sesión está vinculada a su propio almacén.

La navegación no recarga inventario, sesión ni opciones. Al cambiar de estante solo se consulta su vista de ubicaciones, y al guardar se actualiza únicamente el destino. Las pruebas cubren el recorrido desde la advertencia hasta el POST de conteo en zonas raíz y filas anidadas, el foco en cantidad y la exclusión de otros almacenes. No se modificaron tablas ni registros de la base local para esta mejora.

## Encabezado unificado y almacenes sin zonas

La información del almacén, su subtítulo, sucursal y las instrucciones de captura se proyectan dentro del encabezado principal «Conteos» mediante inventoryHeaderContext. Se elimina la tarjeta independiente y se conservan la ruta, los estados, el responsable y las pestañas de navegación.

Cuando no hay ubicaciones, la vista muestra un estado vacío con «Crear zonas» y oculta el selector vacío. El enlace dirige al listado de ubicaciones del almacén de la sesión: /{store}/dashboard/branches/{branch_id}/warehouses/{warehouse_id}/locations. La sucursal se obtiene de la sesión o de las opciones del almacén; no se fijan IDs ni dominio. Para sucursal 1 y almacén 3 se verificó /sorelle/dashboard/branches/1/warehouses/3/locations. Si existen zonas sin ubicaciones finales disponibles, se ofrece «Configurar zonas». No se modificaron datos ni esquema de la base.
