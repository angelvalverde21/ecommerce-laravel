<?php

namespace App\Services\Shopify\InitialImport;

use App\Models\ImportShopifyRun;
use Illuminate\Support\Facades\DB;

class InitialImportWriter
{
    public function task(string $run, string $kind, string $owner): void
    {
        DB::table('import_shopify_tasks')->insertOrIgnore(['run_id' => $run, 'kind' => $kind, 'owner_id' => $owner, 'status' => 'queued', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function save(ImportShopifyRun $run, string $entity, array $payload, ?string $parent = null, bool $includeDependencies = false): void
    {
        if (!in_array($entity, InitialImportQueries::ENTITIES, true)) throw new \RuntimeException('Entidad local no admitida.');
        $identity = $payload['id'] ?? ($parent && isset($payload['index']) ? $parent.'#discount-'.$payload['index'] : null);
        if (!$identity) throw new \RuntimeException('Shopify devolvió un registro sin identificador: '.$entity);
        if ($entity === 'events') {
            if (($payload['__typename'] ?? null) === 'CommentEvent' && (!isset($payload['attachments']) || !is_array($payload['attachments']))) {
                throw new \RuntimeException('El comentario del timeline no incluye el listado verificable de adjuntos.');
            }
            $payload['_import'] = ['timeline_version' => 2, 'retrieved_at' => now()->toIso8601String()];
            if (($payload['__typename'] ?? null) === 'CommentEvent') {
                $payload['_import']['author_status'] = isset($payload['staffAuthor']) ? 'available' : 'requires_read_users';
            }
            app(InitialImportAttachments::class)->stage($run, $payload, $parent);
        }
        DB::table('import_shopify_'.$entity)->updateOrInsert(['run_id' => $run->id, 'shopify_id' => $identity], [
            'parent_shopify_id' => $parent, 'payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $tasks = match ($entity) {
            'orders' => ['order_items', 'shipping_lines', 'discounts', 'order_metafields', 'events'],
            'products' => ['images', 'product_metafields'],
            'variants' => ['variant_metafields'],
            'customers' => ['addresses', 'customer_metafields'],
            'fulfillments' => ['fulfillment_items', 'fulfillment_events'],
            'refunds' => ['refund_items', 'refund_shipping_lines', 'refund_adjustments'],
            default => [],
        };
        foreach ($tasks as $kind) $this->task($run->id, $kind, $identity);
        if ($includeDependencies) {
            if ($entity === 'orders') $this->reference($run, 'customers', $payload['customer']['id'] ?? null);
            if ($entity === 'order_items') {
                $this->reference($run, 'variants', $payload['variant']['id'] ?? null);
                $this->reference($run, 'products', $payload['variant']['product']['id'] ?? null);
            }
            if ($entity === 'variants') $this->reference($run, 'products', $payload['product']['id'] ?? null);
        }
        if ($entity === 'orders') {
            foreach (['transactions', 'fulfillments', 'refunds'] as $child) {
                foreach ($payload[$child] ?? [] as $value) $this->save($run, $child, $value, $identity);
            }
            foreach (['shippingAddress', 'billingAddress'] as $purpose) {
                if (is_array($payload[$purpose] ?? null)) {
                    $address = $payload[$purpose];
                    // Order addresses are snapshots even when they share a Shopify address ID.
                    $address['source_id'] = $address['id'] ?? null;
                    $address['id'] = $identity.'#'.$purpose;
                    $this->save($run, 'addresses', [...$address, 'purpose' => $purpose], $identity);
                }
            }
        }
    }

    private function reference(ImportShopifyRun $run, string $entity, ?string $id): void
    {
        if (!$id || DB::table('import_shopify_'.$entity)->where('run_id', $run->id)->where('shopify_id', $id)->exists()) return;
        $kind = array_search($entity, InitialImportQueries::SNAPSHOTS, true);
        $this->task($run->id, $kind, $id);
    }
}
