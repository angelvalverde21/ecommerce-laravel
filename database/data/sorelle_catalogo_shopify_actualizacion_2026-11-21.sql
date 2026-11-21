-- Sorelle: publicacion de catalogo Shopify. Importar en la base existente desde phpMyAdmin.
-- Solo estructura y registro de migracion; no incluye datos locales ni modifica productos o stock.
-- Requiere las tablas stores, products y migrations previamente actualizadas.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `shopify_product_exports` (
  `id` char(36) NOT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `shop_domain` varchar(120) NOT NULL,
  `handle` varchar(120) NOT NULL,
  `status` varchar(25) NOT NULL DEFAULT 'pending',
  `remote_product_id` varchar(100) DEFAULT NULL,
  `remote_status` varchar(25) DEFAULT NULL,
  `variant_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variant_links`)),
  `payload_hash` varchar(64) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `attempted_at` timestamp NULL DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shopify_exports_product_shop_unique` (`product_id`,`shop_domain`),
  UNIQUE KEY `shopify_product_exports_handle_unique` (`handle`),
  UNIQUE KEY `shopify_exports_remote_unique` (`shop_domain`,`remote_product_id`),
  KEY `shopify_product_exports_store_id_foreign` (`store_id`),
  CONSTRAINT `shopify_product_exports_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `shopify_product_exports_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_21_100000_create_shopify_product_exports', batches.next_batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS next_batch FROM `migrations`) AS batches
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_21_100000_create_shopify_product_exports')
AND EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'shopify_product_exports');
