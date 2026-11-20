# Cuentas de usuario y fichas laborales

`users` concentra nombre, correo, teléfono, tipo y número de documento, estado de acceso y contraseña. Los roles y permisos corresponden a esa cuenta mediante Spatie.

`employees` conserva un `user_id` obligatorio y único, la tienda y los datos laborales: sueldo, comisión, tipo de jornada, fecha de ingreso, horarios, tolerancia, permiso de horas extra, código biométrico, etiqueta de ventas y estado laboral (`status`). No almacena copias de los datos personales ni de los roles. `users.status` controla el acceso; `employees.status` representa la situación laboral y se almacena en la ficha.

## Compatibilidad

Las respuestas de empleados presentan nombre, correo, teléfono, documento y roles como atributos de lectura derivados de `users`. El estado laboral se lee de `employees`; `account.status` expone el estado de acceso de la cuenta. Los formularios de empleados guardan esos datos en la cuenta y los datos laborales en la ficha, dentro de una transacción. Búsquedas, filtros y ordenación consultan la cuenta correspondiente.

Crear un empleado crea su cuenta, la vincula a la tienda y asigna sus roles. Requiere correo disponible, contraseña confirmada de al menos ocho caracteres y un rol. Al editar, una contraseña vacía conserva la existente. Las contraseñas usan el hash de Laravel y nunca aparecen en las respuestas.

El formulario laboral guarda el estado en la ficha y en la cuenta para mantener el comportamiento de acceso existente. Archivar al empleado desactiva ambos estados y conserva el historial. Un cambio directo del acceso en `users` no modifica su estado laboral. La clave foránea impide borrar una cuenta vinculada a una ficha laboral. Las cuentas inactivas no pueden iniciar sesión ni utilizar los endpoints del dashboard con un token anterior.

Se conservaron los identificadores de los 8 empleados, sus cuentas, las 650 asistencias, los 24 horarios y los 557 pagos. Las contraseñas y los permisos existentes permanecieron intactos. Los movimientos de pagos mantienen su relación polimórfica con empleados; el usuario operador del pago no se interpreta como beneficiario.

## Asistencias y resumen laboral

El dashboard del CEO incluye una tabla de pagos de empleados activos de la tienda,
con base quincenal, descuentos, extras, pago individual y suma total calculados en el
servidor. Solo aparece el día 15 y fin de mes; si la fecha cae domingo, aparece el
sábado anterior, usando el calendario de Lima. La quincena mantiene su fecha de fin
nominal y los ajustes se calculan hasta el día efectivo de pago. Esta sección es
independiente del filtro de 7/30/90 días. Un sueldo o información insuficiente queda
pendiente de revisión: se muestra un subtotal identificado y no un total completo.

«Justificar falta» registra motivo obligatorio, usuario y fecha en la asistencia,
reconoce la jornada programada (8 horas netas si no hay horario) y elimina el descuento
por falta, sin generar extras. No reemplaza marcaciones existentes ni un día remoto
o de descanso. Se reserva a master, ceo y admin de la tienda. El módulo compartido
UploadDropzone aparece desde el inicio y permite seleccionar archivos antes de guardar.
Al guardar, se persiste la justificación y después se envían los archivos a su asistencia.
La selección se conserva si falla el guardado; las cargas fallidas pueden reintentarse
sin duplicar la justificación. También se pueden añadir documentos posteriormente.
Son opcionales, PDF/JPEG/PNG/WebP de hasta 10 MB;
sin archivos se indica «Sustento pendiente». Se reutiliza la relación polimórfica
`images`, almacenando los archivos en disco privado y descargándolos por una ruta
autenticada que valida tienda, asistencia y documento. La migración
`2026_11_15_100000_add_absence_justification_to_attendances.php` agrega los campos
de justificación con valor predeterminado false y conserva las marcaciones existentes.

Las imágenes de sustento guardan `thumbnail` (hasta 360 px de ancho), `medium` (hasta
750 px) y `large` (original), sin ampliar imágenes pequeñas ni alterar la proporción.
Las variantes reducidas son WebP. Los PDF conservan únicamente el documento original.
`AttendanceDocumentService` centraliza almacenamiento, metadatos y recuperación de
variantes de imágenes anteriores que solo tenían original. Las descargas aceptan
`variant=thumbnail|medium|large` y mantienen los mismos controles de acceso.
La interfaz obtiene las variantes autenticadas como blobs y utiliza `ImageIndex`
en modo de consulta, sin su cargador ni eliminación genéricos. Los enlaces temporales
de las vistas previas se liberan al cerrar el formulario.

`employees.payroll_uses_attendance` (boolean, true por defecto) controla si las
asistencias afectan al pago, independientemente del estado laboral y del acceso al ERP.
Se configura en Información laboral con «Usar asistencias para calcular el pago».
Al desactivarlo, el empleado habilitado recibe sueldo mensual / 2, incluso sin registros;
no se generan faltas ni días remotos virtuales, y las marcaciones existentes se conservan
sin descuentos ni extras por asistencia. El resumen indica la exención y muestra el
pago íntegro. Un empleado deshabilitado permanece excluido del pago aun con esta opción.
La migración `2026_11_14_210000_add_payroll_uses_attendance_to_employees.php` mantiene
el comportamiento previo para todos los empleados existentes mediante su valor true.

El listado permite habilitar o deshabilitar el estado laboral mediante
`PATCH employees/{employee_id}/status`, reservado a master, ceo y admin de la tienda.
Esta acción modifica solo `employees.status`, conserva el acceso al ERP y todo el
historial. Los empleados deshabilitados se excluyen del listado general de asistencias
y del cálculo económico (`salary_day`, `salary_neto` y `salary_extra` quedan en null).
Su ficha conserva las asistencias reales y el tiempo trabajado, sin generar faltas
ni jornadas remotas virtuales. Al habilitarlos se restablece el cálculo habitual.

La ficha de empleado organiza sus cuatro grupos de datos en tarjetas verticales independientes.
El registro y la edición de asistencia permiten seleccionar Presencial o Remoto para una fecha
concreta mediante `attendances.work_type`, sin modificar el horario semanal. Remoto requiere
un horario laboral válido para ese día (o el horario general del empleado); no se admite en
un día configurado como descanso. Las marcaciones físicas quedan vacías, se computa la jornada
programada y se reconoce el pago diario completo sin tardanzas, faltas ni extras ficticios.
El botón «Editar horas» permite indicar ambas horas para una fecha remota. En ese caso
se guardan en `check_in` y `check_out`, se computa su diferencia menos el descanso y
se calcula el ajuste contra la jornada base de 8 horas. `remote_hours_custom` es una
opción de la petición, no una columna: la respuesta la deriva de ambas horas guardadas.
Volver al horario programado limpia las horas particulares; editar solo la observación
las conserva. El listado muestra también la cantidad de días remotos del período.
Se reutiliza el mismo criterio de cómputo que el remoto programado en los horarios.

Todas las jornadas, presenciales y remotas, descuentan por defecto hasta 60 minutos de
descanso, sin importar si el empleado es de tiempo completo o parcial. El tiempo neto
nunca es negativo. `attendances.break_skipped` permite omitir esa deducción manualmente
para una fecha cuando el empleado informa que no tomó descanso. Esta opción inicia
desmarcada, se conserva al editar y no se infiere ni se sobrescribe al importar CSV.
Para remoto se mantiene el pago del día programado y solo esa excepción manual reconoce
el adicional de descanso trabajado.

Las jornadas presenciales computan la diferencia real entre entrada y salida menos
el descanso, sin limitarla a 8 horas ni a la salida programada. Los campos heredados
`allow_extra_hours` no recortan ese tiempo ni su ajuste económico. Por ejemplo,
10:15–19:23 computa 8 h 08 min y reconoce los 8 minutos adicionales. La tolerancia
solo determina la etiqueta de tardanza.

La migración específica `2026_11_14_200000_add_break_skipped_to_attendances.php` añade
la opción con valor predeterminado false, sin modificar las marcaciones existentes.

Los días computados cuentan las fechas mostradas, incluidas las faltas, como números enteros.
La base de cada quincena es siempre la mitad del sueldo mensual, independientemente de
los días computados. El pago neto mostrado es esa base menos descuentos más extras,
con desglose visible. Este importe no registra un pago ni descuenta
movimientos de caja o adelantos. Si faltan importes calculados, no se presenta un neto de cero.

Se aplicó localmente solo `2026_11_14_190000_add_work_type_to_attendances.php`, que añade una
columna nullable y conserva las asistencias existentes.

La asistencia general utiliza páginas de 20 registros, ordenadas desde la fecha más reciente, con búsqueda por nombre, código biométrico o documento y filtro de fechas. Una marcación incompleta conserva vacía la salida. Se puede corregir mediante el modal de asistencia.

El resumen laboral muestra la quincena actual y las dos anteriores. La primera comprende del 1 al 15 y la segunda del 16 al último día del mes. La actual se calcula hasta hoy, en `America/Lima`. Incluye tiempo computado, tardanzas, faltas, días remotos y marcas incompletas. Las faltas inferidas dependen de los horarios actualmente configurados; no sustituyen una liquidación de nómina.

La importación CSV/TXT del reloj mantiene dos encabezados, separador `;`, código o documento en la segunda columna y fecha `dd/mm/aaaa hh:mm` en la cuarta. Valida toda la carga antes de guardar y rechaza códigos ambiguos o desconocidos. Agrupa por empleado y día, conserva observaciones y no inventa salidas para marcas únicas.

## Migraciones y respaldo local

En `sorelle_production` local se aplicaron exclusivamente estas migraciones de empleados, en orden:

1. `2026_11_06_170000`: ficha laboral independiente.
2. `2026_11_07_010000`: cuenta obligatoria y única.
3. `2026_11_07_020000`: retirada de nombre y correo duplicados.
4. `2026_11_07_030000`: retirada de teléfono, documento, estado y roles duplicados.
5. `2026_11_07_040000`: restauración del estado laboral propio, inicializado desde el estado de acceso conservado.

Antes de retirar las copias se comprobó que coincidían con los datos de las cuentas. No se ejecutaron las migraciones pendientes de Shopify ni la importación de pedidos.

El respaldo actualizado está en `storage/app/private/backups/employee-work-status-20261107-072801/`:

- `before.sql`: respaldo completo anterior al último cambio.
- `sorelle_production.sql.gz`: exportación completa posterior, verificada contra el SQL descomprimido.
- `baseline.json` y `verification.json`: conteos y huellas de integridad de 11 tablas; todos sus datos se conservaron, considerando la retirada prevista de columnas duplicadas.

No se ha importado esta exportación en el hosting. En otra copia antigua se deben respaldar los datos y aplicar únicamente las migraciones correspondientes, sin ejecutar indiscriminadamente todas las pendientes.

## Validación

Pruebas Laravel con SQLite en memoria: transacciones, cuentas obligatorias, contraseñas, roles, desactivación, aislamiento por tienda, datos personales centralizados, CSV, asistencias, horarios, pagos y quincenas. Pruebas Angular para formularios y resumen laboral, además de compilación de desarrollo. Verificación de integridad adicional contra la base local.
