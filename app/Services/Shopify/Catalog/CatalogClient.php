<?php

namespace App\Services\Shopify\Catalog;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CatalogClient
{
    public function domain(): string
    {
        $domain = strtolower(trim((string) config('shopify.store')));
        if (!str_ends_with($domain, '.myshopify.com')) $domain .= '.myshopify.com';
        if (!preg_match('/^[a-z0-9][a-z0-9-]*\.myshopify\.com$/D', $domain)) throw new RuntimeException('Configura el dominio Shopify.');
        return $domain;
    }

    public function request(string $query, array $variables = []): array
    {
        $version = (string) config('shopify.catalog_version');
        if (!preg_match('/^20\d{2}-(01|04|07|10)$/D', $version) || !filled(config('shopify.token'))) throw new RuntimeException('La conexión Shopify está incompleta.');
        // Never automatically retry a mutation: a timeout may hide a successful creation.
        $response = Http::withHeaders(['X-Shopify-Access-Token' => config('shopify.token')])->withOptions(['allow_redirects' => false])->acceptJson()
            ->connectTimeout(8)->timeout(25)->post('https://'.$this->domain().'/admin/api/'.$version.'/graphql.json', ['query' => $query, 'variables' => (object) $variables]);
        if (!$response->successful() || $response->json('errors') || !is_array($response->json('data'))) throw new RuntimeException('Shopify no confirmó la solicitud. Revisa la conexión, permisos o límite de solicitudes y reintenta.');
        return $response->json('data');
    }

    public function verify(): void
    {
        $data = $this->request('query CatalogAccess { shop { myshopifyDomain } currentAppInstallation { accessScopes { handle } } }');
        if (strtolower($data['shop']['myshopifyDomain'] ?? '') !== $this->domain()
            || !in_array('write_products', array_column($data['currentAppInstallation']['accessScopes'] ?? [], 'handle'), true)) throw new RuntimeException('La aplicación necesita write_products y debe pertenecer a la tienda configurada.');
    }

    public const PRODUCT = 'id handle status metafield(namespace: "erp", key: "catalog_export") { value } variants(first: 100) { nodes { id sku selectedOptions { name value } } pageInfo { hasNextPage } }';

    public function find(string $handle): ?array
    {
        $data = $this->request('query FindErpProduct($identifier: ProductIdentifierInput!) { productByIdentifier(identifier: $identifier) { '.self::PRODUCT.' } }', ['identifier' => ['handle' => $handle]]);
        if (!array_key_exists('productByIdentifier', $data)) throw new RuntimeException('Shopify no confirmó el estado del producto.');
        return $data['productByIdentifier'];
    }

    public function create(array $input): array
    {
        $data = $this->request('mutation PublishErpProduct($input: ProductSetInput!) { productSet(input: $input, synchronous: true) { product { '.self::PRODUCT.' } userErrors { field message } } }', ['input' => $input]);
        $result = $data['productSet'] ?? [];
        if (!empty($result['userErrors']) || empty($result['product']['id'])) throw new RuntimeException('Shopify no completó el producto: '.mb_substr(implode(' ', array_column($result['userErrors'] ?? [], 'message')), 0, 800));
        return $result['product'];
    }

    public function findById(string $id): ?array
    {
        $data = $this->request('query ResetErpProduct($id: ID!) { product(id: $id) { '.self::PRODUCT.' } }', ['id' => $id]);
        if (!array_key_exists('product', $data)) throw new RuntimeException('Shopify no confirmó el estado del producto.');
        return $data['product'];
    }

    public function delete(string $id): void
    {
        $data = $this->request('mutation DeleteErpProduct($input: ProductDeleteInput!) { productDelete(input: $input, synchronous: true) { deletedProductId userErrors { field message } } }', ['input' => ['id' => $id]]);
        $result = $data['productDelete'] ?? [];
        if (!empty($result['userErrors']) || ($result['deletedProductId'] ?? null) !== $id) throw new RuntimeException('Shopify no confirmó la eliminación. '.mb_substr(implode(' ', array_column($result['userErrors'] ?? [], 'message')), 0, 800));
    }

    public function assertSkusAvailable(array $skus): void
    {
        $terms = array_map(fn ($sku) => 'sku:"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $sku).'"', $skus);
        $data = $this->request('query CatalogSkuCheck($query: String!) { productVariants(first: 100, query: $query) { nodes { sku } pageInfo { hasNextPage } } }', ['query' => implode(' OR ', $terms)]);
        $connection = $data['productVariants'] ?? null;
        if (!is_array($connection) || !isset($connection['nodes'], $connection['pageInfo']['hasNextPage']) || $connection['pageInfo']['hasNextPage']) throw new RuntimeException('No se pudo verificar si las variantes ya existen en Shopify. Revisa sus SKU antes de enviar.');
        foreach ($connection['nodes'] as $node) {
            if (in_array($node['sku'] ?? null, $skus, true)) throw new RuntimeException('Una variante con el SKU '.$node['sku'].' ya existe en Shopify. Revisa su vínculo antes de crear otro producto.');
        }
    }
}
