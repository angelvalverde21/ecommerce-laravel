<?php

namespace App\Services\Shopify\OrderImport;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;

class ShopifyOrderBulkSource
{
    private function domain(): string
    {
        $domain = strtolower(trim((string) config('shopify.store')));
        $domain = str_ends_with($domain, '.myshopify.com') ? $domain : $domain.'.myshopify.com';
        if (! preg_match('/^[a-z0-9][a-z0-9-]*\.myshopify\.com$/', $domain)) {
            throw new RuntimeException('Dominio Shopify no válido.');
        }

        return $domain;
    }

    protected function request(string $query, array $variables = []): array
    {
        $version = (string) config('shopify.version');
        if (! preg_match('/^20[0-9]{2}-[0-9]{2}$/', $version)) {
            throw new RuntimeException('Versión de Shopify no válida.');
        }
        $attempt = 0;
        while ($attempt < 5) {
            $attempt++;
            $response = Http::withHeaders(['X-Shopify-Access-Token' => config('shopify.token')])
                ->acceptJson()->connectTimeout(10)->timeout(60)
                ->post('https://'.$this->domain().'/admin/api/'.$version.'/graphql.json', [
                    'query' => $query,
                    'variables' => (object) $variables,
                ]);
            $json = $response->json();
            $json = is_array($json) ? $json : null;

            // Retry only an explicit rejection before execution. A network failure or
            // a partial result could hide a successful bulk-operation mutation.
            $rateLimited = empty($json['data']) && ($response->status() === 429
                || ($response->successful() && $this->onlyThrottleErrors($json)));
            if ($rateLimited) {
                if ($attempt === 5) {
                    throw new RuntimeException('Shopify continúa limitando las solicitudes tras cinco intentos. Reanuda más tarde; se conserva el progreso.');
                }
                $delay = $this->retryDelay($response, $json, $attempt);
                if ($delay > 60) {
                    throw new RuntimeException('Shopify requiere una espera mayor de 60 segundos. Reanuda más tarde; se conserva el progreso.');
                }
                Sleep::sleep($delay);

                continue;
            }
            if (! $response->successful()) {
                throw new RuntimeException('La solicitud Shopify falló (HTTP '.$response->status().').');
            }
            if (! is_array($json) || ! empty($json['errors']) || ! isset($json['data'])) {
                throw new RuntimeException('Shopify rechazó la consulta. Revisa permisos y versión de la API.');
            }

            return $json['data'];
        }
        throw new RuntimeException('No se pudo completar la solicitud Shopify.');
    }

    private function onlyThrottleErrors(?array $json): bool
    {
        if (empty($json['errors']) || ! is_array($json['errors'])) {
            return false;
        }
        foreach ($json['errors'] as $error) {
            if (($error['extensions']['code'] ?? null) !== 'THROTTLED') {
                return false;
            }
        }

        return true;
    }

    private function retryDelay(Response $response, ?array $json, int $attempt): int
    {
        $delay = 2 ** ($attempt - 1);
        $retryAfter = $response->header('Retry-After');
        if (is_numeric($retryAfter)) {
            $delay = max($delay, (float) $retryAfter);
        } elseif ($retryAfter && ($timestamp = strtotime($retryAfter)) !== false) {
            $delay = max($delay, $timestamp - time());
        }

        $cost = $json['extensions']['cost'] ?? [];
        $requested = $cost['requestedQueryCost'] ?? null;
        $available = $cost['throttleStatus']['currentlyAvailable'] ?? null;
        $restoreRate = $cost['throttleStatus']['restoreRate'] ?? null;
        if (is_numeric($requested) && is_numeric($available) && is_numeric($restoreRate) && $restoreRate > 0) {
            $delay = max($delay, ($requested - $available) / $restoreRate + 1);
        }

        return $delay > 60 ? 61 : (int) ceil($delay);
    }

    public function verifyAccess(): void
    {
        $data = $this->request('{ shop { myshopifyDomain } currentAppInstallation { accessScopes { handle } } }');
        if (strtolower($data['shop']['myshopifyDomain'] ?? '') !== $this->domain()) {
            throw new RuntimeException('La cuenta Shopify no corresponde a la conexión configurada.');
        }
        $scopes = array_column($data['currentAppInstallation']['accessScopes'] ?? [], 'handle');
        if (! in_array('read_all_orders', $scopes, true) || ! array_intersect(['read_orders', 'write_orders'], $scopes)) {
            throw new RuntimeException('Se requieren read_orders y read_all_orders para importar el historial completo.');
        }
    }

    public function exportQuery(): string
    {
        $money = '{ shopMoney { amount currencyCode } presentmentMoney { amount currencyCode } }';

        return str_replace('__MONEY__', $money, <<<'GQL'
{
  orders {
    edges {
      node {
        __typename id name createdAt updatedAt processedAt cancelledAt closedAt
        displayFinancialStatus displayFulfillmentStatus currencyCode sourceName
        email phone note tags paymentGatewayNames customAttributes { key value }
        totalPriceSet __MONEY__
        currentTotalPriceSet __MONEY__
        currentSubtotalPriceSet __MONEY__
        currentTotalTaxSet __MONEY__
        totalShippingPriceSet __MONEY__
        totalDiscountsSet __MONEY__
        totalRefundedSet __MONEY__
        customer { id displayName email phone }
        shippingAddress { name firstName lastName phone company address1 address2 city province provinceCode country countryCodeV2 zip }
        billingAddress { name firstName lastName phone company address1 address2 city province provinceCode country countryCodeV2 zip }
        lineItems {
          edges {
            node {
              __typename id name sku quantity currentQuantity
              originalUnitPriceSet __MONEY__
              discountedUnitPriceSet __MONEY__
              discountedTotalSet __MONEY__
              variant { id sku product { id } }
            }
          }
        }
        shippingLines {
          edges {
            node {
              __typename id title code source
              originalPriceSet __MONEY__
              discountedPriceSet __MONEY__
            }
          }
        }
      }
    }
  }
}
GQL);
    }

    public function start(): string
    {
        $data = $this->request(
            'mutation ExportOrders($query: String!) { bulkOperationRunQuery(query: $query) { bulkOperation { id } userErrors { message } } }',
            ['query' => $this->exportQuery()]
        );
        $result = $data['bulkOperationRunQuery'] ?? [];
        if (! empty($result['userErrors']) || empty($result['bulkOperation']['id'])) {
            throw new RuntimeException('No se pudo iniciar la exportación masiva. Revisa si hay otra operación activa y los permisos.');
        }

        return $result['bulkOperation']['id'];
    }

    public function operation(string $id): array
    {
        $data = $this->request(
            'query ExportStatus($id: ID!) { node(id: $id) { ... on BulkOperation { id status errorCode rootObjectCount objectCount url } } }',
            ['id' => $id]
        );
        if (empty($data['node']['status'])) {
            throw new RuntimeException('No se encontró la exportación masiva.');
        }
        if ($data['node']['status'] === 'COMPLETED') {
            foreach (['rootObjectCount', 'objectCount'] as $field) {
                if (! isset($data['node'][$field]) || ! ctype_digit((string) $data['node'][$field])) {
                    throw new RuntimeException('La exportación no tiene un conteo verificable de registros.');
                }
            }
        }

        return $data['node'];
    }

    public function download(string $url, string $path): void
    {
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('La descarga Shopify requiere HTTPS.');
        }
        // The signed download must never receive the Shopify access token.
        $response = Http::connectTimeout(10)->timeout(600)->withOptions([
            'sink' => $path, 'allow_redirects' => false,
        ])->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('No se pudo descargar la exportación (HTTP '.$response->status().').');
        }
    }
}
