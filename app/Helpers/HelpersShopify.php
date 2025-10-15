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