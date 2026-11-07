# Cuentas de usuario y fichas laborales

`users` concentra nombre, correo, teléfono, tipo y número de documento, estado de acceso y contraseña. Los roles y permisos corresponden a esa cuenta mediante Spatie.

`employees` conserva un `user_id` obligatorio y único, la tienda y los datos laborales: sueldo, comisión, tipo de jornada, fecha de ingreso, horarios, tolerancia, permiso de horas extra, código biométrico, etiqueta de ventas y estado laboral (`status`). No almacena copias de los datos personales ni de los roles. `users.status` controla el acceso; `employees.status` representa la situación laboral y se almacena en la ficha.

## Compatibilidad

Las respuestas de empleados presentan nombre, correo, teléfono, documento y roles como atributos de lectura derivados de `users`. El estado laboral se lee de `employees`; `account.status` expone el estado de acceso de la cuenta. Los formularios de empleados guardan esos datos en la cuenta y los datos laborales en la ficha, dentro de una transacción. Búsquedas, filtros y ordenación consultan la cuenta correspondiente.

Crear un empleado crea su cuenta, la vincula a la tienda y asigna sus roles. Requiere correo disponible, contraseña confirmada de al menos ocho caracteres y un rol. Al editar, una contraseña vacía conserva la existente. Las contraseñas usan el hash de Laravel y nunca aparecen en las respuestas.

El formulario laboral guarda el estado en la ficha y en la cuenta para mantener el comportamiento de acceso existente. Archivar al empleado desactiva ambos estados y conserva el historial. Un cambio directo del acceso en `users` no modifica su estado laboral. La clave foránea impide borrar una cuenta vinculada a una ficha laboral. Las cuentas inactivas no pueden iniciar sesión ni utilizar los endpoints del dashboard con un token anterior.

Se conservaron los identificadores de los 8 empleados, sus cuentas, las 650 asistencias, los 24 horarios y los 557 pagos. Las contraseñas y los permisos existentes permanecieron intactos. Los movimientos de pagos mantienen su relación polimórfica con empleados; el usuario operador del pago no se interpreta como beneficiario.

## Asistencias y resumen laboral

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