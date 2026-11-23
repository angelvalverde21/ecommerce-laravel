<?php

namespace App\Services\Shopify\Catalog;

use App\Models\Product;
use App\Models\ShopifyProductExport;
use App\Models\Store;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CatalogPublisher
{
    public function __construct(private CatalogPayload $payload, private CatalogClient $client) {}

    public function connection(Store $store): array
    {
        $domain = null; $issues = [];
        try { $domain = $this->client->domain(); } catch (RuntimeException $e) { $issues[] = $e->getMessage(); }
        if (!filled(config('shopify.token'))) $issues[] = 'Falta configurar el acceso a Shopify.';
        if (!preg_match('/^20\d{2}-(01|04|07|10)$/D', (string) config('shopify.catalog_version'))) $issues[] = 'Configura una versión válida de la API de catálogo Shopify.';
        if ($store->slug !== config('shopify.catalog_store_slug')) $issues[] = 'Esta tienda no está vinculada a la conexión de catálogo Shopify.';
        if (!Schema::hasTable('shopify_product_exports')) $issues[] = 'Falta instalar la migración de publicación de productos.';
        return ['ready' => !$issues, 'domain' => $domain, 'issues' => $issues];
    }

    public function preview(Store $store, int $actor, array $ids, string $status, string $mode = 'catalog'): array
    {
        $connection = $this->connection($store);
        $products = $store->products()->whereIn('id', $ids)->with([
            'options' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'), 'variants.variant_option_values.optionValue',
        ])->get()->keyBy('id');
        abort_unless($products->count() === count($ids), 404, 'Uno de los productos no pertenece a esta tienda.');
        $exports = Schema::hasTable('shopify_product_exports') ? ShopifyProductExport::whereIn('product_id', $ids)->where('shop_domain', $connection['domain'])->get()->keyBy('product_id') : collect();
        $items = [];
        foreach ($ids as $id) {
            $product = $products[$id];
            $item = ['id' => $id, 'name' => $product->name, 'status' => 'ready', 'issues' => [], 'variants' => [], 'token' => null];
            $export = $exports->get($id);
            if ($mode === 'reset') {
                if (!$export || $export->store_id !== $store->id || filled($product->origin)) {
                    $item['status'] = 'invalid'; $item['issues'][] = 'Solo se pueden restablecer productos exportados desde este ERP.';
                } elseif ($connection['ready']) {
                    $item['token'] = Crypt::encryptString(json_encode(['action' => 'reset', 'store' => $store->id, 'actor' => $actor,
                        'product' => $id, 'domain' => $connection['domain'], 'export' => $export->id, 'expires' => now()->addMinutes(30)->timestamp]));
                }
                $items[] = $item;
                continue;
            }
            if ($export && in_array($export->status, ['resetting', 'reset_failed'], true) && $mode !== 'reset') {
                $item['status'] = 'invalid'; $item['issues'][] = 'Completa el restablecimiento antes de volver a exportar.';
                $items[] = $item; continue;
            }
            if ($export?->status === 'synced' || filled($product->origin) || (!$export && $product->variants->contains(fn ($variant) => filled($variant->shopify_variant_id)))) {
                $item['status'] = 'linked'; $item['issues'][] = 'Ya tiene un vínculo externo. No se creará otro producto.';
            } else {
                try {
                    $data = $this->payload->build($product, $status);
                    $item['variants'] = $data['variants'];
                    if ($connection['ready']) $item['token'] = Crypt::encryptString(json_encode(['store' => $store->id, 'actor' => $actor, 'product' => $id,
                        'domain' => $connection['domain'], 'status' => $status, 'hash' => $data['hash'], 'expires' => now()->addMinutes(30)->timestamp]));
                } catch (ValidationException $e) { $item['status'] = 'invalid'; $item['issues'] = array_merge(...array_values($e->errors())); }
            }
            $items[] = $item;
        }
        return ['connection' => $connection, 'target_status' => $status, 'mode' => $mode, 'items' => $items];
    }

    public function publish(Store $store, int $actor, int $id, string $token): array
    {
        $connection = $this->connection($store);
        abort_unless($connection['ready'], 422, implode(' ', $connection['issues']));
        try { $approved = json_decode(Crypt::decryptString($token), true, 32, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { abort(422, 'La revisión venció o no es válida. Revisa nuevamente la selección.'); }
        abort_unless(!isset($approved['action']) && ($approved['store'] ?? null) === $store->id && ($approved['actor'] ?? null) === $actor
            && ($approved['product'] ?? null) === $id && ($approved['domain'] ?? null) === $connection['domain']
            && ($approved['expires'] ?? 0) > now()->timestamp, 422, 'Vuelve a revisar la selección antes de publicar.');
        $product = $store->products()->findOrFail($id);
        $data = $this->payload->build($product, $approved['status']);
        abort_unless(hash_equals($approved['hash'], $data['hash']), 409, 'El producto cambió desde la revisión. Revisa nuevamente su información.');
        $export = DB::transaction(function () use ($product, $connection, $actor) {
            $product->newQuery()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $export = ShopifyProductExport::firstOrCreate(['product_id' => $product->id, 'shop_domain' => $connection['domain']],
                ['id' => (string) Str::uuid(), 'handle' => 'erp-'.Str::uuid(), 'store_id' => $product->store_id]);
            if ($export->status === 'synced') return $export;
            abort_if(in_array($export->status, ['resetting', 'reset_failed'], true), 409, 'Completa el restablecimiento antes de publicar.');
            abort_if(filled($product->origin) || (!$export->attempted_at && $product->variants()->whereNotNull('shopify_variant_id')->exists()), 409, 'El producto ya tiene un vínculo externo.');
            abort_if($export->status === 'sending' && $export->attempted_at?->gt(now()->subMinutes(3)), 409, 'Este producto ya se está enviando. Espera antes de reintentar.');
            $export->update(['status' => 'sending', 'attempted_at' => now(), 'user_id' => $actor, 'last_error' => null]);
            return $export;
        });
        if ($export->status === 'synced') return $this->result($export);
        try {
            $this->client->verify(true);
            $publications = $this->client->salesPublications();
            $remote = $this->client->find($export->handle);
            if ($remote && ($remote['metafield']['value'] ?? '') !== $export->id) throw new RuntimeException('El producto encontrado en Shopify no corresponde a este envío. Revisa el vínculo.');
            if ($remote && $export->payload_hash && $export->payload_hash !== $data['hash']) throw new RuntimeException('El producto local cambió después de un envío sin confirmar. Revisa el producto en Shopify antes de continuar.');
            if (!$remote) {
                $this->client->assertSkusAvailable(array_column($data['variants'], 'sku'));
                $export->update(['payload_hash' => $data['hash']]);
                $input = $data['input'] + ['handle' => $export->handle,
                    'metafields' => [['namespace' => 'erp', 'key' => 'catalog_export', 'type' => 'single_line_text_field', 'value' => $export->id]]];
                $remote = $this->client->create($input);
            }
            $this->client->enableSalesChannels($remote['id'], $publications);
            $this->persist($product, $export, $remote, $data);
            return $this->result($export->fresh());
        } catch (\Throwable $error) {
            $message = $error instanceof RuntimeException ? $error->getMessage() : 'No pudimos confirmar el envío. Reintentar comprobará primero si el producto ya existe en Shopify.';
            // Retain the handle and payload fingerprint to reconcile ambiguous network outcomes.
            $export->update(['status' => 'failed', 'last_error' => mb_substr($message, 0, 1200)]);
            return ['product_id' => $id, 'status' => 'failed', 'message' => $message];
        }
    }

    public function reset(Store $store, int $actor, int $id, string $token): array
    {
        $connection = $this->connection($store);
        abort_unless($connection['ready'], 422, implode(' ', $connection['issues']));
        try { $approved = json_decode(Crypt::decryptString($token), true, 32, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { abort(422, 'Vuelve a revisar la selección antes de restablecer.'); }
        abort_unless(($approved['action'] ?? null) === 'reset' && ($approved['store'] ?? null) === $store->id
            && ($approved['actor'] ?? null) === $actor && ($approved['product'] ?? null) === $id
            && ($approved['domain'] ?? null) === $connection['domain'] && ($approved['expires'] ?? 0) > now()->timestamp,
            422, 'Vuelve a revisar la selección antes de restablecer.');
        $product = $store->products()->findOrFail($id);
        abort_if(filled($product->origin), 409, 'Este producto tiene un vínculo externo de importación.');
        $export = DB::transaction(function () use ($product, $connection, $approved, $actor) {
            $product->newQuery()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $export = ShopifyProductExport::where('product_id', $product->id)->where('shop_domain', $connection['domain'])->first();
            if (!$export) return null;
            abort_unless($export->id === ($approved['export'] ?? null) && $export->store_id === $product->store_id, 409, 'La exportación cambió. Revisa nuevamente.');
            abort_if(in_array($export->status, ['sending', 'resetting'], true) && $export->attempted_at?->gt(now()->subMinutes(3)), 409, 'Hay una operación en curso. Espera antes de reintentar.');
            $export->update(['status' => 'resetting', 'attempted_at' => now(), 'user_id' => $actor]);
            return $export;
        });
        if (!$export) return ['product_id' => $id, 'status' => 'reset', 'message' => 'El producto ya fue restablecido.'];
        try {
            $this->client->verify();
            $remote = $export->remote_product_id ? $this->client->findById($export->remote_product_id) : $this->client->find($export->handle);
            if ($remote) {
                if (($remote['metafield']['value'] ?? null) !== $export->id) throw new RuntimeException('El producto de Shopify no corresponde a esta exportación. No se eliminó.');
                $this->client->delete($remote['id']);
            }
            DB::transaction(function () use ($product, $export) {
                foreach ($export->variant_links ?? [] as $id => $link) {
                    $product->variants()->withoutGlobalScopes()->whereKey($id)->where('shopify_variant_id', $link['variant_id'])->update(['shopify_variant_id' => null]);
                }
                $export->delete();
            });
            return ['product_id' => $id, 'status' => 'reset', 'message' => 'Eliminado de Shopify. Puedes volver a exportarlo.'];
        } catch (\Throwable $error) {
            $message = $error instanceof RuntimeException ? $error->getMessage() : 'No se pudo confirmar la eliminación. Reintenta para comprobar su estado.';
            $export->update(['status' => 'reset_failed', 'last_error' => mb_substr($message, 0, 1200)]);
            return ['product_id' => $id, 'status' => 'failed', 'message' => $message];
        }
    }

    private function persist(Product $product, ShopifyProductExport $export, array $remote, array $data): void
    {
        if (!preg_match('~^gid://shopify/Product/\d+$~D', $remote['id'] ?? '') || !empty($remote['variants']['pageInfo']['hasNextPage'])) throw new RuntimeException('Shopify devolvió un producto incompleto. Reintenta para consultar su estado.');
        $nodes = $remote['variants']['nodes'] ?? [];
        if (count($nodes) !== count($data['variants'])) throw new RuntimeException('Las variantes recibidas no coinciden con el ERP. Revisa el producto en Shopify.');
        $links = [];
        foreach ($data['variants'] as $variant) {
            $expected = collect($variant['optionValues'])->pluck('name', 'optionName')->sortKeys()->all();
            $match = collect($nodes)->first(fn ($node) => ($node['sku'] ?? '') === $variant['sku']
                && collect($node['selectedOptions'] ?? [])->pluck('value', 'name')->sortKeys()->all() === $expected);
            if (!$match || !preg_match('~^gid://shopify/ProductVariant/\d+$~D', $match['id'] ?? '')) throw new RuntimeException('No se pudo vincular una talla con su variante Shopify. Revisa el producto antes de continuar.');
            $links[$variant['id']] = ['variant_id' => $match['id'], 'sku' => $variant['sku'], 'options' => $expected];
        }
        DB::transaction(function () use ($product, $export, $remote, $data, $links) {
            foreach ($links as $id => $link) $product->variants()->withoutGlobalScopes()->whereKey($id)->update(['shopify_variant_id' => $link['variant_id']]);
            $export->update(['remote_product_id' => $remote['id'], 'remote_status' => $remote['status'], 'variant_links' => $links,
                'payload_hash' => $data['hash'], 'status' => 'synced', 'synced_at' => now(), 'last_error' => null]);
        });
    }

    private function result(ShopifyProductExport $export): array
    {
        return ['product_id' => $export->product_id, 'status' => 'synced', 'remote_product_id' => $export->remote_product_id,
            'remote_status' => $export->remote_status, 'message' => 'Producto vinculado con Shopify.'];
    }
}
