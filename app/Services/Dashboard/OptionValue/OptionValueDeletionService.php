<?php

namespace App\Services\Dashboard\OptionValue;

use App\Models\Store;
use App\Models\Variant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OptionValueDeletionService
{
    public function delete(Store $store, int $productId, int $optionId, int $valueId): array
    {
        // Discover dependencies before taking row locks. MySQL needs just one
        // metadata query, regardless of how many tables exist in the database.
        $references = $this->variantReferences();
        $connection = DB::connection();
        $mysql = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true);
        $previousLockWait = null;
        if ($mysql) {
            $previousLockWait = (int) $connection->selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS value')->value;
            $connection->statement('SET SESSION innodb_lock_wait_timeout = '.min(5, $previousLockWait));
        }
        try {
            return DB::transaction(function () use ($store, $productId, $optionId, $valueId, $references) {
                $product = $store->products()->whereKey($productId)->lockForUpdate()->firstOrFail();
                $option = $product->options()->whereKey($optionId)->lockForUpdate()->firstOrFail();
                $value = $option->option_values()->whereKey($valueId)->lockForUpdate()->firstOrFail();
                $ids = DB::table('variant_option_values')->where('option_value_id', $value->id)
                    ->orderBy('variant_id')->pluck('variant_id')->unique()->values();
                $variants = Variant::withoutGlobalScopes()->where('product_id', $product->id)
                    ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                if ($variants->count() !== $ids->count()) {
                    throw ValidationException::withMessages(['value' => 'El valor tiene relaciones inconsistentes con otro producto. Revisa sus variantes.']);
                }
                if ($variants->contains(fn ($variant) => (int) $variant->stock !== 0)) {
                    throw ValidationException::withMessages(['value' => 'No se puede eliminar este valor porque alguna de sus variantes tiene stock.']);
                }
                // Keep history protected even when its FK would cascade or become null.
                if ($ids->isNotEmpty()) {
                    foreach ($references as $reference) {
                        if (DB::table($reference['table'])->whereIn($reference['column'], $ids)->exists()) {
                            throw ValidationException::withMessages(['value' => 'No se puede eliminar este valor porque sus variantes tienen historial de inventario, compras o producción.']);
                        }
                    }
                }

                // Remove every option link of the affected variants, not only the selected value.
                DB::table('variant_option_values')->whereIn('variant_id', $ids)->delete();
                Variant::withoutGlobalScopes()->where('product_id', $product->id)->whereIn('id', $ids)->delete();
                $value->delete();

                return ['option_id' => $option->id, 'option_value_id' => $valueId, 'deleted_variant_ids' => $ids->all()];
            }, 2);
        } catch (QueryException $exception) {
            if ($mysql && in_array((int) ($exception->errorInfo[1] ?? 0), [1205, 1213], true)) {
                throw ValidationException::withMessages(['value' => 'El producto está siendo modificado en otra operación. Espera unos segundos e inténtalo nuevamente.']);
            }
            throw $exception;
        } finally {
            if ($previousLockWait !== null) {
                $connection->statement('SET SESSION innodb_lock_wait_timeout = '.$previousLockWait);
            }
        }
    }

    private function variantReferences(): array
    {
        $connection = DB::connection();
        $prefix = $connection->getTablePrefix();
        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $schema = $connection->getDatabaseName();
            $keys = $connection->select(
                'SELECT DISTINCT TABLE_NAME AS table_name, COLUMN_NAME AS column_name
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_SCHEMA = ?
                   AND REFERENCED_TABLE_NAME = ? AND REFERENCED_COLUMN_NAME = ?
                   AND TABLE_NAME <> ?',
                [$schema, $schema, $prefix.'variants', 'id', $prefix.'variant_option_values']
            );

            return array_map(fn ($key) => [
                'table' => $prefix !== '' && str_starts_with($key->table_name, $prefix)
                    ? substr($key->table_name, strlen($prefix)) : $key->table_name,
                'column' => $key->column_name,
            ], $keys);
        }

        $references = [];
        foreach (Schema::getTables() as $table) {
            if ($table['name'] === 'variant_option_values') {
                continue;
            }
            foreach (Schema::getForeignKeys($table['name']) as $key) {
                if ($key['foreign_table'] === 'variants' && count($key['columns']) === 1) {
                    $references[] = ['table' => $table['name'], 'column' => $key['columns'][0]];
                }
            }
        }

        return $references;
    }
}
