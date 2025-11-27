<?php

use Illuminate\Support\Facades\Http;

function responseShopify(string $query): array
{
  return Http::withHeaders([
    'Content-Type' => 'application/json',
    'X-Shopify-Access-Token' => config('shopify.token'),
  ])->post("https://" . config('shopify.store') . ".myshopify.com/admin/api/" . config('shopify.version') . "/graphql.json", ['query' => $query])
    ->json();
}

//==================================== FUNCIONES DE IMAGENES DE SHOPIFY  ====================================

if (!function_exists('shopify_resize')) {
  /**
   * Resize para imágenes de Shopify.
   * Inserta _WIDTHxHEIGHT antes de la extensión.
   */
  function shopify_resize(string $src, int $width, int $height): string
  {
    // Obtener la extensión (jpg, png, webp...)
    $extension = pathinfo($src, PATHINFO_EXTENSION);

    // Cortar el final (".jpg" o ".png") para insertar tamaño
    $base = substr($src, 0, - (strlen($extension) + 1));

    return "{$base}_{$width}x{$height}.{$extension}";
  }
}

if (!function_exists('shopify_thumbnail')) {
  /**
   * Thumbnail 100x100
   */
  function shopify_thumbnail(string $src): string
  {
    return shopify_resize($src, 200, 200);
  }
}

if (!function_exists('shopify_medium')) {
  /**
   * Medium 300x300
   */
  function shopify_medium(string $src): string
  {
    return shopify_resize($src, 500, 500);
  }
}

if (!function_exists('shopify_large')) {
  /**
   * Large 800x800
   */
  function shopify_large(string $src): string
  {
    return shopify_resize($src, 1000, 1000);
  }
}

//==================================== FIN FUNCIONES DE IMAGENES DE SHOPIFY  ====================================