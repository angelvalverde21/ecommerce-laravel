<?php

namespace App\Services\Shopify\Catalog;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CatalogClient
{
    public const INITIAL_VARIANT_QUANTITY = 4;
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

    public function verify(bool $withPublications = false): void
    {
        $data = $this->request('query CatalogAccess { shop { myshopifyDomain } currentAppInstallation { accessScopes { handle } } }');
        if (strtolower($data['shop']['myshopifyDomain'] ?? '') !== $this->domain()
            || !in_array('write_products', array_column($data['currentAppInstallation']['accessScopes'] ?? [], 'handle'), true)) throw new RuntimeException('La aplicación necesita write_products y debe pertenecer a la tienda configurada.');
        if ($withPublications) {
            $scopes = array_column($data['currentAppInstallation']['accessScopes'] ?? [], 'handle');
            if (!in_array('write_publications', $scopes, true)) {
                throw new RuntimeException('Para seleccionar todos los canales de venta, habilita read_publications y write_publications en la aplicación Shopify y actualiza su autorización.');
            }
            if (!in_array('write_inventory', $scopes, true)) {
                throw new RuntimeException('Para crear las variantes con 4 unidades y seguimiento de inventario, habilita write_inventory en la aplicación Shopify y actualiza su autorización.');
            }
        }
    }

    public function salesPublications(): array
    {
        $ids = []; $after = null;
        do {
            $data = $this->request('query CatalogSalesChannels($after: String) { publications(first: 100, after: $after, catalogType: APP) { nodes { id } pageInfo { hasNextPage endCursor } } }', ['after' => $after]);
            $page = $data['publications'] ?? null;
            if (!is_array($page) || !isset($page['nodes'], $page['pageInfo']['hasNextPage'])) throw new RuntimeException('No se pudieron consultar todos los canales de venta de Shopify.');
            foreach ($page['nodes'] as $node) {
                if (!preg_match('~^gid://shopify/Publication/\d+$~D', $node['id'] ?? '')) throw new RuntimeException('Shopify devolvió un canal de venta inválido.');
                $ids[] = $node['id'];
            }
            $next = $page['pageInfo']['endCursor'] ?? null;
            if ($page['pageInfo']['hasNextPage'] && (!$next || $next === $after)) throw new RuntimeException('No se pudieron consultar todos los canales de venta de Shopify.');
            $after = $next;
        } while ($page['pageInfo']['hasNextPage']);
        if (!$ids) throw new RuntimeException('No hay canales de venta disponibles para esta aplicación Shopify. Revisa sus permisos.');
        return array_values(array_unique($ids));
    }

    public function enableSalesChannels(string $productId, array $publicationIds): void
    {
        foreach (array_chunk($publicationIds, 100) as $ids) {
            $data = $this->request('mutation CatalogEnableSalesChannels($id: ID!, $input: [PublicationInput!]!) { publishablePublish(id: $id, input: $input) { publishable { ... on Product { id } } userErrors { field message } } }',
                ['id' => $productId, 'input' => array_map(fn ($id) => ['publicationId' => $id], $ids)]);
            $result = $data['publishablePublish'] ?? [];
            if (!empty($result['userErrors']) || ($result['publishable']['id'] ?? null) !== $productId) throw new RuntimeException('El producto se creó, pero Shopify no confirmó todos los canales de venta. Reintenta para completar el envío sin duplicarlo. '.mb_substr(implode(' ', array_column($result['userErrors'] ?? [], 'message')), 0, 800));
        }
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
        $data = $this->request('query CatalogInventoryLocation { location { id isActive fulfillsOnlineOrders } }');
        $location = $data['location'] ?? null;
        if (!is_array($location) || !preg_match('~^gid://shopify/Location/\d+$~D', $location['id'] ?? '')
            || !($location['isActive'] ?? false) || !($location['fulfillsOnlineOrders'] ?? false)) {
            throw new RuntimeException('La sucursal principal de Shopify debe estar activa y habilitada para preparar pedidos online antes de exportar.');
        }
        // Set opening stock only during creation. Retries reconcile the existing product
        // through find() and never overwrite quantities changed by sales or manual edits.
        $input['variants'] = array_map(fn ($variant) => $variant + [
            'inventoryItem' => ['tracked' => true],
            'inventoryQuantities' => [['locationId' => $location['id'], 'name' => 'available', 'quantity' => self::INITIAL_VARIANT_QUANTITY]],
        ], $input['variants']);
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
