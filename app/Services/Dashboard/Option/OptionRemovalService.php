<?php

namespace App\Services\Dashboard\Option;

use App\Models\Option;
use App\Models\Status;
use App\Models\Store;
use App\Models\Variant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OptionRemovalService
{
    public function remove(Store $store, int $productId, int $optionId): array
    {
        $connection = DB::connection();
        $mysql = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true);
        $previousLockWait = null;
        if ($mysql) {
            $previousLockWait = (int) $connection->selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS value')->value;
            $connection->statement('SET SESSION innodb_lock_wait_timeout = '.min(5, $previousLockWait));
        }
        try {
            return DB::transaction(function () use ($store, $productId, $optionId) {
                $product = $store->products()->whereKey($productId)->lockForUpdate()->firstOrFail();
                $option = $product->options()->whereKey($optionId)->lockForUpdate()->firstOrFail();
                $valueIds = $option->option_values()->lockForUpdate()->pluck('id');
                $ids = DB::table('variant_option_values')->where(fn ($query) => $query
                    ->where('option_id', $option->id)->orWhereIn('option_value_id', $valueIds)
                )->pluck('variant_id')->unique();
                $variants = Variant::withoutGlobalScopes()->where('product_id', $product->id)
                    ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                if ($variants->count() !== $ids->count()) {
                    throw ValidationException::withMessages(['option' => 'El bloque tiene relaciones inconsistentes con otro producto.']);
                }
                $hasChildren = Option::where('parent_id', $option->id)->exists();
                $archive = $valueIds->isNotEmpty() || $ids->isNotEmpty() || $hasChildren;
                if ($archive) {
                    $option->update(['archived_at' => now()]);
                    Variant::withoutGlobalScopes()->where('product_id', $product->id)->whereIn('id', $ids)
                        ->where('status', Status::ACTIVE)->update(['status' => Status::ARCHIVED]);
                } else {
                    $option->delete();
                }
                $hiddenIds = Variant::withoutGlobalScopes()->where('product_id', $product->id)
                    ->where('status', '!=', Status::ACTIVE)->pluck('id')->all();

                return ['option_id' => $optionId, 'archived' => $archive, 'hidden_variant_ids' => $hiddenIds];
            }, 2);
        } catch (QueryException $exception) {
            if ($mysql && in_array((int) ($exception->errorInfo[1] ?? 0), [1205, 1213], true)) {
                throw ValidationException::withMessages(['option' => 'El producto está siendo modificado. Espera unos segundos e inténtalo nuevamente.']);
            }
            throw $exception;
        } finally {
            if ($previousLockWait !== null) {
                $connection->statement('SET SESSION innodb_lock_wait_timeout = '.$previousLockWait);
            }
        }
    }
}
