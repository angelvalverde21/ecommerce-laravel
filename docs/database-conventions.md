# Convenciones de base de datos

Las columnas `created_at` y `updated_at` deben ser las últimas de cada tabla, en ese orden. Si solo existe una, debe quedar al final.

Al crear tablas, declarar los campos funcionales antes de `timestamps()`. Al agregar campos en MySQL, ubicarlos antes de `created_at` mediante `after()` sobre el último campo funcional. No cambiar el tipo, la nulabilidad, los valores predeterminados ni los valores de las fechas para ajustar su posición.

La migración `2026_11_07_050000_place_audit_timestamps_last` corrige las nueve tablas locales que tenían campos posteriores a las fechas. Conserva sus definiciones originales mediante `SHOW CREATE TABLE`. También incluye las tablas de infraestructura que tienen esas columnas; no añade un `updated_at` donde no existe. En SQLite omite el cambio físico de posición.

Aplicada en `sorelle_production` local. Se verificaron los datos de las 76 tablas funcionales y las definiciones de todas las columnas, excluyendo únicamente su posición. El registro técnico de migraciones incorpora la nueva ejecución.

Respaldo y exportación actualizados: `storage/app/private/backups/column-order-20261107-073115/`. Incluye `before.sql`, `sorelle_production.sql.gz`, `baseline.json` y `verification.json`. El hosting no fue modificado y la migración de Shopify permanece pendiente.
