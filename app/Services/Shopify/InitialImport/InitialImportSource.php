<?php

namespace App\Services\Shopify\InitialImport;

use App\Services\Shopify\OrderImport\ShopifyOrderBulkSource;
use Illuminate\Support\Sleep;
use RuntimeException;

class InitialImportSource extends ShopifyOrderBulkSource
{
    public array $throttle = [];
    private int $delay = 1;
    private ?bool $staffAccess = null;
    protected function apiVersion(): string { return (string) config('shopify.initial_import_version'); }

    protected function request(string $query, array $variables = []): array
    {
        Sleep::sleep($this->delay);
        return parent::request($query, $variables);
    }

    protected function observeCost(array $cost): void
    {
        $this->throttle = $cost['throttleStatus'] ?? [];
        $available = (float) ($this->throttle['currentlyAvailable'] ?? 2000);
        $maximum = min(2000, (float) ($this->throttle['maximumAvailable'] ?? 2000));
        $rate = (float) ($this->throttle['restoreRate'] ?? 100);
        $this->delay = $rate > 0 ? max(1, (int) ceil((min(100, $maximum) - $available) / $rate)) : 1;
        if ($this->delay > 60) throw new RuntimeException('Shopify requiere una pausa prolongada. Reanuda la carga más tarde.');
    }

    public function verifyAll(): void
    {
        $this->verifyAccess();
        $data = $this->request('{ currentAppInstallation { accessScopes { handle } } }');
        $scopes = array_column($data['currentAppInstallation']['accessScopes'] ?? [], 'handle');
        $this->staffAccess = in_array('read_users', $scopes, true);
        foreach (['products', 'customers'] as $entity) {
            if (!array_intersect(['read_'.$entity, 'write_'.$entity], $scopes)) {
                throw new RuntimeException('Falta el permiso read_'.$entity.' en la aplicación Shopify.');
            }
        }
    }

    public function startDataset(string $kind): string
    {
        $data = $this->request('mutation InitialExport($query: String!) { bulkOperationRunQuery(query: $query) { bulkOperation { id } userErrors { field message } } }', ['query' => InitialImportQueries::bulk($kind)]);
        $result = $data['bulkOperationRunQuery'] ?? [];
        if (!empty($result['userErrors']) || empty($result['bulkOperation']['id'])) {
            // These errors describe field/schema validation, never the downloaded customer payload.
            $messages = implode(' ', array_map(fn ($error) => $error['message'] ?? '', $result['userErrors'] ?? []));
            throw new RuntimeException('Shopify no pudo preparar '.$kind.': '.mb_substr($messages, 0, 1200));
        }
        return $result['bulkOperation']['id'];
    }

    public function page(string $kind, string $owner, ?string $cursor): array
    {
        $data = $this->request(InitialImportQueries::connection($kind, $kind === 'events' && $this->canReadStaff()), ['id' => $owner, 'cursor' => $cursor]);
        $field = InitialImportQueries::CONNECTIONS[$kind][1];
        $connection = $data['node'][$field] ?? null;
        if (!is_array($connection) || !isset($connection['edges'], $connection['pageInfo']['hasNextPage'])) {
            throw new RuntimeException('No se pudo recuperar '.$kind.'. Revisa el acceso al recurso o si fue eliminado durante la carga.');
        }
        if ($kind === 'events') $connection['hasTimelineComment'] = $data['node']['hasTimelineComment'] ?? null;
        return $connection;
    }

    public function batch(string $kind, array $owners): array
    {
        $data = $this->request(InitialImportQueries::batch($kind, $kind === 'events' && $this->canReadStaff()), ['ids' => $owners]);
        $field = InitialImportQueries::CONNECTIONS[$kind][1];
        $pages = [];
        foreach ($data['nodes'] ?? [] as $node) {
            if (!isset($node['id'], $node[$field]['edges'], $node[$field]['pageInfo']['hasNextPage'])) throw new RuntimeException('No se pudo recuperar un recurso de '.$kind.'. Revisa si fue eliminado durante la carga.');
            $pages[$node['id']] = $node[$field];
            if ($kind === 'events') $pages[$node['id']]['hasTimelineComment'] = $node['hasTimelineComment'] ?? null;
        }
        if (count($pages) !== count($owners)) throw new RuntimeException('Shopify devolvió un lote incompleto de '.$kind.'.');
        return $pages;
    }

    private function canReadStaff(): bool
    {
        if ($this->staffAccess === null) {
            $data = $this->request('{ currentAppInstallation { accessScopes { handle } } }');
            $this->staffAccess = in_array('read_users', array_column($data['currentAppInstallation']['accessScopes'] ?? [], 'handle'), true);
        }
        return $this->staffAccess;
    }

    public function newOrders(\Carbon\CarbonImmutable $from, \Carbon\CarbonImmutable $until, ?string $cursor): array
    {
        $query = 'created_at:>="'.$from->utc()->toIso8601ZuluString().'" created_at:<="'.$until->utc()->toIso8601ZuluString().'"';
        $data = $this->request(InitialImportQueries::newOrders(), ['query' => $query, 'cursor' => $cursor]);
        $page = $data['orders'] ?? null;
        if (!is_array($page) || !isset($page['nodes'], $page['pageInfo']['hasNextPage'])) throw new RuntimeException('Shopify devolvió una búsqueda incompleta de pedidos.');
        return $page;
    }

    public function snapshots(string $kind, array $owners): array
    {
        $data = $this->request(InitialImportQueries::snapshots($kind), ['ids' => $owners]);
        $expected = ['customers' => 'Customer', 'products' => 'Product', 'variants' => 'ProductVariant'][InitialImportQueries::SNAPSHOTS[$kind]];
        $records = [];
        foreach ($data['nodes'] ?? [] as $node) {
            if (empty($node['id']) || ($node['__typename'] ?? null) !== $expected || !in_array($node['id'], $owners, true)) throw new RuntimeException('No se pudo recuperar una referencia del pedido. Revisa si fue eliminada en Shopify.');
            $records[$node['id']] = $node;
        }
        if (count($records) !== count($owners)) throw new RuntimeException('Shopify devolvió referencias incompletas del pedido.');
        return $records;
    }
}
