<?php

namespace App\Services\Shopify\InitialImport;

use Illuminate\Support\Facades\DB;

/** Resolves pictures by Shopify IDs within the same import, never by product names. */
class InitialImportOrderItemImages
{
    public function enrich(string $runId, array $items): array
    {
        $variantIds = array_values(array_unique(array_filter(array_column(array_column($items, 'variant'), 'id'))));
        $variants = DB::table('import_shopify_variants')->where('run_id', $runId)->whereIn('shopify_id', $variantIds)->get()
            ->mapWithKeys(fn ($row) => [$row->shopify_id => json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR)])->all();
        $productIds = [];
        foreach ($items as $item) {
            $variant = $variants[$item['variant']['id'] ?? ''] ?? [];
            $id = $item['variant']['product']['id'] ?? $variant['product']['id'] ?? $item['product']['id'] ?? null;
            if ($id) $productIds[] = $id;
        }
        $productIds = array_values(array_unique($productIds));
        $products = DB::table('import_shopify_products')->where('run_id', $runId)->whereIn('shopify_id', $productIds)->get()
            ->mapWithKeys(fn ($row) => [$row->shopify_id => json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR)])->all();
        $gallery = [];
        foreach (DB::table('import_shopify_images')->where('run_id', $runId)->whereIn('parent_shopify_id', $productIds)->orderBy('id')->get() as $row) {
            $image = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
            if (!isset($gallery[$row->parent_shopify_id]) && $this->safe($image)) $gallery[$row->parent_shopify_id] = $image;
        }

        return array_map(function ($item) use ($variants, $products, $gallery) {
            $variant = $variants[$item['variant']['id'] ?? ''] ?? [];
            $productId = $item['variant']['product']['id'] ?? $variant['product']['id'] ?? $item['product']['id'] ?? '';
            $candidates = [$item['image'] ?? null, $variant['image'] ?? null, $products[$productId]['featuredImage'] ?? null, $gallery[$productId] ?? null];
            $images = array_values(array_filter($candidates, fn ($image) => $this->safe($image)));
            $urls = array_values(array_unique(array_column($images, 'url')));
            return [...$item, 'display_image' => $urls ? ['url' => $urls[0], ...$this->sizes($urls[0]),
                'fallback_urls' => array_slice($urls, 1), 'fallback_images' => array_map(fn ($url) => $this->sizes($url), array_slice($urls, 1)),
                'altText' => $images[0]['altText'] ?? $item['name'] ?? $item['title'] ?? 'Imagen del artículo'] : null];
        }, $items);
    }

    private function sizes(string $url): array
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
        $query['width'] = 128;
        $query['height'] = 152;
        unset($query['crop']);
        $base = explode('?', explode('#', $url, 2)[0], 2)[0];
        return ['url_thumbnail' => $base.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986), 'url_large' => $url];
    }

    private function safe(?array $image): bool
    {
        if (empty($image['url']) || !is_string($image['url'])) return false;
        $parts = parse_url($image['url']);
        $host = strtolower($parts['host'] ?? '');
        return ($parts['scheme'] ?? '') === 'https' && !isset($parts['user']) && !isset($parts['pass'])
            && (!isset($parts['port']) || $parts['port'] === 443)
            && ($host === 'cdn.shopify.com' || str_ends_with($host, '.shopifycdn.com'));
    }
}
