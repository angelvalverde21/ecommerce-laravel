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
                'full_name' => 'Ropa',
                'children' => [
                    [
                        'name' => 'Hombre',
                        'full_name' => 'Ropa > Hombre'
                    ],
                    [
                        'name' => 'Mujer',
                        'full_name' => 'Ropa > Mujer'
                    ],
                    [
                        'name' => 'Niños',
                        'full_name' => 'Ropa > Niños'
                    ],
                ],
            ],
            [
                'name' => 'Calzado',
                'full_name' => 'Calzado',
                'children' => [

                    [
                        'name' => 'Zapatillas',
                        'full_name' => 'Calzado > Zapatillas'

                    ],

                    [
                        'name' => 'Botas',
                        'full_name' => 'Calzado > Botas'

                    ],

                    [
                        'name' => 'Sandalias',
                        'full_name' => 'Calzado > Sandalias'

                    ],
                ],
            ],
            [
                'name' => 'Accesorios',
                'full_name' => 'Accesorios',
                'children' => [
                    [
                        'name' => 'Bolsos',
                        'full_name' => 'Accesorios > Bolsos'

                    ],
                    [
                        'name' => 'Gorros',
                        'full_name' => 'Accesorios > Gorros'

                    ],
                    [
                        'name' => 'Cinturones',
                        'full_name' => 'Accesorios > Cinturones'

                    ],
                ],
            ],
            [
                'name' => 'Belleza y Cuidado Personal',
                'full_name' => 'Belleza y Cuidado Personal',
            ],
            [
                'name' => 'Hogar y Decoración',
                'full_name' => 'Hogar y Decoración',
            ],
            [
                'name' => 'Electrónica',
                'full_name' => 'Electrónica',
            ],
            [
                'name' => 'Deportes y Fitness',
                'full_name' => 'Deportes y Fitness',
            ],
            [
                'name' => 'Ofertas',
                'full_name' => 'Ofertas',
            ],
        ];

        $sortOrder = 1;

        foreach ($categories as $catData) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($catData['name'])],
                [
                    'name' => $catData['name'],
                    'full_name' => $catData['full_name'],
                    'slug' => Str::slug($catData['name']),
                    'parent_id' => null,
                    'sort_order' => $sortOrder++,
                    'status' => true,
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
                            'full_name' => $child['full_name'],
                            'slug' => Str::slug($child['name']),
                            'parent_id' => $category->id,
                            'sort_order' => $childSort++,
                            'status' => true,
                            'user_id' => $userId,
                            'store_id' => $storeId,
                        ]
                    );
                }
            }
        }
    }
}
