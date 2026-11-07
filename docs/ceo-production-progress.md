# Avance de producciones en el dashboard del CEO

El bloque presenta el avance de recepción de producciones de la tienda actual. Utiliza `manufactures` de tipo `production`, sus cantidades de `manufacture_variant` y los movimientos asociados de `kardexes`. Es una consulta de lectura.

Para cada variante, el avance computable es el menor entre la cantidad planificada y la recepción neta positiva (entradas menos salidas). Los sobrantes de una variante no compensan faltantes de otra. El porcentaje acumulado pondera por unidades; solo llega a 100 % cuando todas las cantidades previstas están cubiertas.

Los estados son pendiente, en curso, completada y sin cantidades planificadas. Describen recepción frente al plan, sin inferir etapas de fabricación o retrasos. Los sobrantes y las recepciones netas negativas muestran avisos.

El detalle incluye todas las producciones pendientes, aunque sean anteriores al período seleccionado, y las completadas con movimientos en ese período. Presenta hasta seis filas, priorizando pendientes, con acceso al listado y a cada producción. El resumen acumulado considera todas las producciones de la tienda hasta la fecha de consulta.

El dashboard del CEO se actualiza cada 60 segundos mientras la pestaña esté visible. Evita solicitudes superpuestas y conserva el último reporte con un aviso si falla una actualización. Los otros dashboards mantienen su comportamiento anterior.

Las pruebas cubren aislamiento por tienda, salidas, sobrantes por variante, producciones antiguas pendientes o completadas recientemente, registros futuros, cantidades sin plan, redondeo y estructura no disponible. Angular verifica las barras, los enlaces, la conservación ante errores y la actualización automática.