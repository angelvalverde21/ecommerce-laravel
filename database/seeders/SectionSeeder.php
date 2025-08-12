<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $rootSection = Section::create([
            'store_id' => 1,        // Ajusta al store correspondiente
            'parent_id' => null,
            'name' => 'Produccion textil',
            'slug' => 'clothing-production',
            'order' => 1,
        ]);

        // Define las subsecciones
        $subsections = [
            'Patrones',
            'Muestras',
            'Compra de tela',
            'Corte', // o 'Garments'
            'Confeccion', // o 'Garments'
            'Others',
        ];

        // Crea las subsecciones relacionadas a la raíz
        foreach ($subsections as $index => $name) {
            Section::create([
                'store_id' => 1,          // Igual que la raíz
                'parent_id' => $rootSection->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'order' => $index + 1,
            ]);
        }
    }
}
