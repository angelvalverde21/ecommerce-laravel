# Cambios de productos: precios y archivado de opciones

Este paquete contiene únicamente las tres migraciones de base de datos del trabajo de productos. No contiene registros de productos, credenciales, `.env`, imágenes ni código de la aplicación.

En el entorno local ya se aplicaron sobre `sorelle_production`. El respaldo previo y la verificación están en `storage/app/private/backups/product-catalog-20261106-090834/`.

## Cambios incluidos

| Migración | Cambios |
| --- | --- |
| `2026_11_05_100000_add_quantity_price_total_to_prices_table.php` | Agrega cantidad, precio unitario y total a `prices`; completa los campos nuevos de los precios existentes. |
| `2026_11_05_110000_create_price_labels_table.php` | Crea `price_labels`, agrega su relación a `prices` y registra los nombres existentes. |
| `2026_11_06_100000_add_archive_to_product_options.php` | Agrega `archived_at` y la columna calculada `active_name` a `options`; reemplaza su índice único para permitir opciones archivadas. |

Las migraciones no contienen el nombre de la base de datos. Se ejecutan sobre la conexión configurada en el `.env` del servidor. La columna calculada requiere un MySQL/MariaDB que admita columnas generadas almacenadas e indexadas.

## Aplicar en el hosting

1. Obtén un respaldo reciente de la base de datos del hosting antes de aplicar el cambio.
2. Copia los tres archivos de `migrations/` de este paquete a `database/migrations/` del proyecto Laravel del hosting, conservando sus nombres.
3. Comprueba que la conexión del servidor apunta a la base correcta. Conserva el `.env` del hosting; no lo reemplaces por el del equipo local.
4. Desde la raíz de Laravel, revisa las consultas previstas:

```sh
php artisan migrate --force --pretend --no-interaction \
  --path=database/migrations/2026_11_05_100000_add_quantity_price_total_to_prices_table.php \
  --path=database/migrations/2026_11_05_110000_create_price_labels_table.php \
  --path=database/migrations/2026_11_06_100000_add_archive_to_product_options.php
```

5. Aplica únicamente estas tres migraciones:

```sh
php artisan migrate --force --no-interaction \
  --path=database/migrations/2026_11_05_100000_add_quantity_price_total_to_prices_table.php \
  --path=database/migrations/2026_11_05_110000_create_price_labels_table.php \
  --path=database/migrations/2026_11_06_100000_add_archive_to_product_options.php
```

No ejecutes `migrate:fresh`, `migrate:refresh`, seeders ni una importación completa de la base local para aplicar estas mejoras sobre los datos en vivo. Estas migraciones agregan la estructura y conservan los registros existentes. Si una migración ya figura como aplicada, Laravel la omite.

## Código de la aplicación

Este paquete de base de datos debe acompañar al backend actualizado de productos y al frontend Angular compilado. Las funciones de duplicación, precios, sugerencias, confirmaciones y archivado también dependen de ese código. No basta con subir las migraciones para incorporar las pantallas y endpoints.

El comando anterior selecciona únicamente estas migraciones; no aplica las migraciones pendientes del módulo de inventario.

## Comprobación posterior

```sh
php artisan migrate:status \
  --path=database/migrations/2026_11_05_100000_add_quantity_price_total_to_prices_table.php \
  --path=database/migrations/2026_11_05_110000_create_price_labels_table.php \
  --path=database/migrations/2026_11_06_100000_add_archive_to_product_options.php
```

Verifica que las tres aparezcan como ejecutadas. Después comprueba el listado y la edición de productos, los precios por cantidad y el archivado de opciones.

El hosting no ha sido modificado desde este equipo.
