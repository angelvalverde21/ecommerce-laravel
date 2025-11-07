<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 🔹 Configura el usuario y tienda por defecto (ajústalo a tu proyecto)
        $userId = 1;
        $storeId = 1;

        // 🔹 Categorías principales
        $categories = [
            [
                'name' => 'Ropa',
                'is_color' => true,
                'is_size' => true,
                'children' => [
                    ['name' => 'Hombre'],
                    ['name' => 'Mujer'],
                    ['name' => 'Niños'],
                ],
            ],
            [
                'name' => 'Calzado',
                'is_color' => true,
                'is_size' => true,
                'children' => [
                    ['name' => 'Zapatillas'],
                    ['name' => 'Botas'],
                    ['name' => 'Sandalias'],
                ],
            ],
            [
                'name' => 'Accesorios',
                'is_color' => true,
                'is_size' => false,
                'children' => [
                    ['name' => 'Bolsos'],
                    ['name' => 'Gorros'],
                    ['name' => 'Cinturones'],
                ],
            ],
            [
                'name' => 'Belleza y Cuidado Personal',
                'is_color' => false,
                'is_size' => false,
            ],
            [
                'name' => 'Hogar y Decoración',
                'is_color' => false,
                'is_size' => false,
            ],
            [
                'name' => 'Electrónica',
                'is_color' => false,
                'is_size' => false,
            ],
            [
                'name' => 'Deportes y Fitness',
                'is_color' => true,
                'is_size' => true,
            ],
            [
                'name' => 'Ofertas',
                'is_color' => true,
                'is_size' => false,
            ],
        ];

        $sortOrder = 1;

        foreach ($categories as $catData) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($catData['name'])],
                [
                    'name' => $catData['name'],
                    'slug' => Str::slug($catData['name']),
                    'parent_id' => null,
                    'sort_order' => $sortOrder++,
                    'status' => true,
                    'is_color' => $catData['is_color'] ?? true,
                    'is_size' => $catData['is_size'] ?? false,
                    'user_id' => $userId,
                    'store_id' => $storeId,
                ]
            );

            // 🔹 Si tiene subcategorías
            if (!empty($catData['children'])) {
                $childSort = 1;

                foreach ($catData['children'] as $child) {
                    Category::updateOrCreate(
                        [
                            'slug' => Str::slug($child['name']),
                        ],
                        [
                            'name' => $child['name'],
                            'slug' => Str::slug($child['name']),
                            'parent_id' => $category->id,
                            'sort_order' => $childSort++,
                            'status' => true,
                            'is_color' => $catData['is_color'] ?? true,
                            'is_size' => $catData['is_size'] ?? false,
                            'user_id' => $userId,
                            'store_id' => $storeId,
                        ]
                    );
                }
            }
        }
    }
}
