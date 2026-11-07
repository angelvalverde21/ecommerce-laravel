<?php

namespace App\Services\Shopify\OrderImport;

use App\Models\Order;
use App\Models\ShopifyOrderImport;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ShopifyOrderImportRunner
{
    public function __construct(private ShopifyOrderBulkSource $source) {}

    public function start(Store $store): ShopifyOrderImport
    {
        if (ShopifyOrderImport::where('store_id', $store->id)->whereNotIn('status', ['completed', 'cancelled'])->exists()) {
            throw new RuntimeException('Ya existe una ejecución pendiente. Usa --resume con su identificador.');
        }
        $this->source->verifyAccess();
        $run = ShopifyOrderImport::create([
            'id' => (string) Str::uuid(),
            'store_id' => $store->id,
            'status' => 'exporting',
            'phase' => 'export',
        ]);
        try {
            $run->update(['operation_id' => $this->source->start()]);
        } catch (\Throwable $exception) {
            $run->update(['status' => 'failed', 'error_code' => 'EXPORT_START_FAILED']);
            throw $exception;
        }

        return $run->refresh();
    }

    public function advance(ShopifyOrderImport $run): bool
    {
        if ($run->status === 'completed') {
            return true;
        }
        if ($run->status === 'cancelled') {
            throw new RuntimeException('La ejecución fue cancelada.');
        }
        if ($run->status === 'failed') {
            $phaseStatus = [
                'export' => 'exporting',
                'stage' => 'staging',
                'import' => 'importing',
            ];
            $run->update(['status' => $phaseStatus[$run->phase], 'error_code' => null]);
        }
        try {
            if ($run->phase === 'export') {
                if (! $run->operation_id) {
                    $run->update(['operation_id' => $this->source->start()]);
                }
                $operation = $this->source->operation($run->operation_id);
                if (in_array($operation['status'], ['FAILED', 'CANCELED', 'EXPIRED'], true)) {
                    $run->update(['operation_id' => null]);
                    throw new RuntimeException('La exportación Shopify no está completa: '.$operation['status'].'. No se importarán resultados parciales. Reanuda para solicitar una exportación nueva.');
                }
                if ($operation['status'] !== 'COMPLETED') {
                    return false;
                }
                $expected = (int) ($operation['rootObjectCount'] ?? 0);
                $run->update([
                    'expected_orders' => $expected,
                    'expected_rows' => (int) $operation['objectCount'],
                ]);
                if (! $expected) {
                    if ($run->expected_rows !== 0) {
                        throw new RuntimeException('El conteo de la exportación es inconsistente.');
                    }
                    $run->update(['status' => 'completed', 'completed_at' => now()]);

                    return true;
                }
                $path = $this->filePath($run);
                if (! is_file($path)) {
                    if (empty($operation['url'])) {
                        throw new RuntimeException('La exportación completa no tiene un archivo descargable.');
                    }
                    $temporary = $path.'.download';
                    $this->source->download($operation['url'], $temporary);
                    if (! rename($temporary, $path)) {
                        throw new RuntimeException('No se pudo guardar la exportación descargada.');
                    }
                }
                $run->update(['phase' => 'stage', 'status' => 'staging']);
                $this->stage($run, $path);
                $this->verifyStagedCounts($run);
                $run->update(['phase' => 'import', 'status' => 'importing']);
            } elseif ($run->phase === 'stage') {
                $this->stage($run, $this->filePath($run));
                $this->verifyStagedCounts($run);
                $run->update(['phase' => 'import', 'status' => 'importing']);
            }
            $this->importStaged($run);
            $run->update(['status' => 'completed', 'completed_at' => now(), 'error_code' => null]);

            return true;
        } catch (\Throwable $exception) {
            $run->update(['status' => 'failed', 'error_code' => strtoupper($run->phase).'_FAILED']);
            throw $exception;
        }
    }

    public function filePath(ShopifyOrderImport $run): string
    {
        if (! Str::isUuid($run->id)) {
            throw new RuntimeException('Identificador de ejecución inválido.');
        }
        $directory = storage_path('app/private/shopify-order-imports');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el directorio privado de importación.');
        }

        return $directory.'/'.$run->id.'.jsonl';
    }

    private function verifyStagedCounts(ShopifyOrderImport $run): void
    {
        $rows = DB::table('shopify_order_import_rows')->where('import_id', $run->id);
        $orders = (clone $rows)->where('type', 'Order')->count();
        if ($run->expected_orders === null || $run->expected_rows === null
            || $orders !== (int) $run->expected_orders || $rows->count() !== (int) $run->expected_rows) {
            throw new RuntimeException('El archivo no contiene todos los pedidos y artículos anunciados por Shopify.');
        }
    }

    public function stage(ShopifyOrderImport $run, string $path): void
    {
        $stream = fopen($path, 'rb');
        if (! $stream) {
            throw new RuntimeException('No se encuentra el archivo privado de la exportación.');
        }
        try {
            if (fseek($stream, (int) $run->file_offset) !== 0) {
                throw new RuntimeException('No se pudo reanudar la lectura del archivo.');
            }
            $batch = [];
            while (($line = fgets($stream)) !== false) {
                if (! trim($line)) {
                    continue;
                }
                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                $type = $record['__typename'] ?? null;
                if (! in_array($type, ['Order', 'LineItem', 'ShippingLine'], true) || empty($record['id'])) {
                    throw new RuntimeException('La exportación contiene un registro no reconocido.');
                }
                if ($type !== 'Order' && empty($record['__parentId'])) {
                    throw new RuntimeException('Hay una línea sin pedido de origen.');
                }
                $batch[] = [
                    'import_id' => $run->id,
                    'source_id' => $record['id'],
                    'parent_id' => $record['__parentId'] ?? null,
                    'type' => $type,
                    'payload' => json_encode($record, JSON_THROW_ON_ERROR),
                ];
                if (count($batch) >= 100) {
                    $this->stageBatch($run, $batch, ftell($stream));
                    $batch = [];
                }
            }
            if (! feof($stream)) {
                throw new RuntimeException('La lectura del archivo terminó antes de completarse.');
            }
            if ($batch) {
                $this->stageBatch($run, $batch, ftell($stream));
            }
            $orphans = DB::table('shopify_order_import_rows as children')
                ->where('children.import_id', $run->id)->whereNotNull('children.parent_id')
                ->whereNotExists(function ($query) use ($run) {
                    $query->selectRaw('1')->from('shopify_order_import_rows as parents')
                        ->where('parents.import_id', $run->id)->where('parents.type', 'Order')
                        ->whereColumn('parents.source_id', 'children.parent_id');
                })->exists();
            if ($orphans) {
                throw new RuntimeException('Hay artículos o envíos sin su pedido en la exportación.');
            }
        } finally {
            fclose($stream);
        }
    }

    private function stageBatch(ShopifyOrderImport $run, array $batch, int $offset): void
    {
        DB::transaction(function () use ($run, $batch, $offset) {
            $inserted = DB::table('shopify_order_import_rows')->insertOrIgnore($batch);
            $run->increment('staged_rows', $inserted);
            $run->update(['file_offset' => $offset]);
        });
    }

    public function importStaged(ShopifyOrderImport $run): void
    {
        DB::table('shopify_order_import_rows')
            ->where('import_id', $run->id)
            ->where('type', 'Order')
            ->where('id', '>', $run->last_row_id)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($run) {
                foreach ($rows as $row) {
                    DB::transaction(fn () => $this->importOrder($run, $row));
                }
            });
    }

    private function importOrder(ShopifyOrderImport $run, object $row): void
    {
        $source = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
        $gid = $source['id'];
        if (! preg_match('~^gid://shopify/Order/[0-9]+$~', $gid)) {
            throw new RuntimeException('Identificador Shopify de pedido inválido.');
        }

        $existing = DB::table('orders')
            ->where('store_id', $run->store_id)
            ->whereIn('shopify_order_id', [$gid, basename($gid)])
            ->exists();
        if ($existing) {
            $run->increment('skipped_orders');
            $run->update(['last_row_id' => $row->id]);

            return;
        }

        $children = DB::table('shopify_order_import_rows')
            ->where('import_id', $run->id)
            ->where('parent_id', $gid)
            ->orderBy('id')->get()
            ->map(fn ($child) => json_decode($child->payload, true, 512, JSON_THROW_ON_ERROR));
        $items = $children->where('__typename', 'LineItem')->values()->all();
        $shipping = $children->where('__typename', 'ShippingLine')->values()->all();
        [$snapshots, $normalized, $unmatched] = $this->prepareItems($items, $run->store_id);
        $total = $source['currentTotalPriceSet'] ?? $source['totalPriceSet'] ?? null;

        $id = DB::table('orders')->insertGetId([
            'store_id' => $run->store_id,
            'user_id' => null,
            'shopify_order_id' => $gid,
            'shopify_order_name' => $source['name'],
            'variants' => json_encode($snapshots, JSON_THROW_ON_ERROR),
            'currency_code' => $this->currency($total),
            'subtotal' => $this->money($source['currentSubtotalPriceSet'] ?? null),
            'igv' => $this->money($source['currentTotalTaxSet'] ?? null),
            'delivery_cost' => $this->money($source['totalShippingPriceSet'] ?? null),
            'total' => $this->money($total),
            'status' => $this->status($source),
            'is_cod' => false,
            'freight_collect' => false,
            'courier_address_id' => null,
            'delivery_type' => null,
            'created_at' => $this->date($source['createdAt']),
            'updated_at' => $this->date($source['updatedAt'] ?? $source['createdAt']),
        ]);
        foreach ($normalized as $item) {
            DB::table('order_items')->insert([...$item, 'order_id' => $id]);
        }

        $source['shippingLines'] = $shipping;
        DB::table('order_shopify_details')->insert([
            'order_id' => $id,
            'import_id' => $run->id,
            'financial_status' => $source['displayFinancialStatus'] ?? null,
            'fulfillment_status' => $source['displayFulfillmentStatus'] ?? null,
            'source_updated_at' => $this->date($source['updatedAt'] ?? $source['createdAt']),
            'source_payload' => json_encode($source, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $unresolved = $this->addresses(Order::findOrFail($id), $source);
        $run->increment('imported_orders');
        $run->increment('unmatched_variants', $unmatched);
        $run->increment('unresolved_addresses', $unresolved);
        $run->update(['last_row_id' => $row->id]);
    }

    private function prepareItems(array $items, int $storeId): array
    {
        $snapshots = [];
        $normalized = [];
        $unmatched = 0;
        foreach ($items as $item) {
            $sku = $item['sku'] ?? $item['variant']['sku'] ?? null;
            $variantId = $this->matchVariant($sku, $storeId);
            if (! $variantId) {
                $unmatched++;
            }
            $priceSet = $item['discountedUnitPriceSet'] ?? $item['originalUnitPriceSet'] ?? null;
            $price = $this->money($priceSet, 6);
            $common = [
                'shopify_line_item_id' => $item['id'],
                'shopify_variant_id' => $item['variant']['id'] ?? null,
                'sku' => $sku,
                'name' => $item['name'],
                'quantity' => (int) $item['quantity'],
            ];
            $snapshots[] = [
                ...$common,
                'id' => $variantId,
                'price' => $price,
                'variant_option_values' => [],
            ];
            $normalized[] = [
                ...$common,
                'variant_id' => $variantId,
                'unit_price' => $price,
                'line_total' => $this->money($item['discountedTotalSet'] ?? null),
                'currency_code' => $this->currency($priceSet),
                'source_payload' => json_encode($item, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return [$snapshots, $normalized, $unmatched];
    }

    private function matchVariant(?string $sku, int $storeId): ?int
    {
        if (! $sku) {
            return null;
        }
        $matches = DB::table('variants')
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->where('products.store_id', $storeId)
            ->where('variants.sku', $sku)
            ->limit(2)->get(['variants.id']);

        return $matches->count() === 1 ? (int) $matches->first()->id : null;
    }

    private function addresses(Order $order, array $source): int
    {
        $unresolved = 0;
        foreach (['shippingAddress' => 'shipping', 'billingAddress' => 'billing'] as $key => $purpose) {
            $address = $source[$key] ?? null;
            if (! is_array($address)) {
                continue;
            }
            $district = null;
            if (($address['countryCodeV2'] ?? '') === 'PE') {
                $candidates = [$address['zip'] ?? ''];
                foreach ($source['customAttributes'] ?? [] as $attribute) {
                    if (in_array(strtolower($attribute['key'] ?? ''), ['ubigeo', 'district_id', 'codigo_ubigeo'], true)) {
                        $candidates[] = $attribute['value'] ?? '';
                    }
                }
                foreach ($candidates as $candidate) {
                    if (preg_match('/^[0-9]{6}$/', (string) $candidate) && DB::table('districts')->where('id', (int) $candidate)->exists()) {
                        $district = (int) $candidate;
                        break;
                    }
                }
            }
            if (! $district) {
                $unresolved++;
            }
            $order->addresses()->create([
                'purpose' => $purpose,
                'name' => $address['name'] ?? trim(($address['firstName'] ?? '').' '.($address['lastName'] ?? '')),
                'primary' => $address['address1'] ?? null,
                'secondary' => $address['address2'] ?? null,
                'phone' => $address['phone'] ?? $source['phone'] ?? null,
                'district_id' => $district,
                'country_code' => $address['countryCodeV2'] ?? null,
                'city' => $address['city'] ?? null,
                'province' => $address['province'] ?? null,
                'postal_code' => $address['zip'] ?? null,
            ]);
        }

        return $unresolved;
    }

    private function money(?array $value, int $decimals = 2): string
    {
        $amount = $value['shopMoney']['amount'] ?? null;
        if (! is_string($amount) || ! preg_match('/^[0-9]{1,12}(?:\\.[0-9]{1,'.$decimals.'})?$/', $amount)) {
            throw new RuntimeException('El pedido contiene un importe ausente o incompatible con la precisión local.');
        }

        return $amount;
    }

    private function currency(?array $value): string
    {
        $currency = $value['shopMoney']['currencyCode'] ?? '';
        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new RuntimeException('Moneda de pedido inválida.');
        }

        return $currency;
    }

    private function date(string $value): string
    {
        return CarbonImmutable::parse($value)->setTimezone(config('app.timezone'))->toDateTimeString();
    }

    private function status(array $source): string
    {
        if (! empty($source['cancelledAt'])) {
            return 'cancelado';
        }
        if (($source['displayFulfillmentStatus'] ?? '') === 'FULFILLED') {
            return 'enviado';
        }
        if (in_array($source['displayFulfillmentStatus'] ?? '', ['PARTIALLY_FULFILLED', 'IN_PROGRESS'], true)) {
            return 'preparacion';
        }

        return match ($source['displayFinancialStatus'] ?? '') {
            'PAID' => 'pago_confirmado',
            'PARTIALLY_PAID' => 'pago_recibido',
            'PENDING', 'REFUNDED' => 'en_espera',
            default => 'registrado',
        };
    }
}
