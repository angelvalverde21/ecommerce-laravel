<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL supports physical column ordering; SQLite does not.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['couriers', 'employees', 'inventories', 'inventory_sessions', 'job_batches', 'oauth_access_tokens', 'options', 'prices', 'warehouses'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $timestamps = array_values(array_intersect(['created_at', 'updated_at'], $columns));
            if (! $timestamps || array_slice($columns, -count($timestamps)) === $timestamps) {
                continue;
            }

            $otherColumns = array_values(array_diff($columns, $timestamps));
            $previous = end($otherColumns);
            $quote = fn (string $name): string => '`'.str_replace('`', '``', $name).'`';
            $definition = array_values((array) DB::selectOne('SHOW CREATE TABLE '.$quote($table)))[1];
            $changes = [];
            foreach ($timestamps as $column) {
                $prefix = $quote($column).' ';
                $line = collect(explode("\n", $definition))
                    ->map(fn ($line) => trim($line))
                    ->first(fn ($line) => str_starts_with($line, $prefix));
                if (! $line) {
                    throw new RuntimeException('No se encontró la definición de '.$table.'.'.$column);
                }

                // Reuse the exact definition to preserve type, defaults and attributes.
                $changes[] = 'MODIFY COLUMN '.rtrim($line, ',').' AFTER '.$quote($previous);
                $previous = $column;
            }
            DB::statement('ALTER TABLE '.$quote($table).' '.implode(', ', $changes));
        }
    }

    public function down(): void
    {
        // Cosmetic ordering only; keep the convention when rolling back.
    }
};
