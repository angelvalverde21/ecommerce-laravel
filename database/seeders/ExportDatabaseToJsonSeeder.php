<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportDatabaseToJsonSeeder extends Seeder
{
    public function run()
    {
        // Tablas que quieres exportar
        $tables = [
            'shopify_products',
            'shopify_variants'
        ];

        foreach ($tables as $table) {

            $data = DB::table($table)->get();

            $path = database_path("seeders/json/{$table}.json");

            // Crear carpeta si no existe
            if (!File::exists(dirname($path))) {
                File::makeDirectory(dirname($path), 0755, true);
            }

            // Guardar JSON bonito
            File::put(
                $path,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            $this->command->info("Exportado: {$table}");
        }
    }
}