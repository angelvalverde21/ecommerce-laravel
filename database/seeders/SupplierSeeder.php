<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $suplliers = [
            [
                "name" => "Sra Padid",
                "address" => "San Pedro Piso 10",
                "email" => NULL,
                "store_id" => 1,
                "phone" => "997057384",
                "identity_id" => 1,
                "document_number" => NULL,
            ],
            [
                "name" => "Yoni",
                "address" => "Mercado la Polvora 3er piso",
                "email" => NULL,
                "store_id" => 1,
                "phone" => "989620873",
                "identity_id" => 1,
                "document_number" => NULL,
            ],
                        [
                "name" => "Flotex (Alejandro)",
                "address" => "Jr. Antonio Bazo 1070, La Victoria",
                "email" => NULL,
                "store_id" => 1,
                "phone" => "933560867",
                "identity_id" => 1,
                "document_number" => NULL,
            ],
                        [
                "name" => "Comercial Reynaldo",
                "address" => "Galeria Sucre 1er Piso",
                "email" => NULL,
                "store_id" => 1,
                "phone" => "946158238",
                "identity_id" => 1,
                "document_number" => NULL,
            ],
                        [
                "name" => "Elmer",
                "address" => "Parinacochas con humbolt",
                "email" => NULL,
                "store_id" => 1,
                "phone" => "963796086",
                "identity_id" => 1,
                "document_number" => NULL,
            ],
                        [
                "name" => "Cecilia Import Export Matias EIRL",
                "address" => "Galeria La victoria Interior C221",
                "email" => NULL,
                "store_id" => 1,
                "phone" => "970390875",
                "identity_id" => 1,
                "document_number" => NULL,
            ],
                        [
                "name" => "Leoncia Roque",
                "address" => "Galeria Leo",
                "email" => NULL,
                "store_id" => 1,
                "phone" => "662",
                "identity_id" => 1,
                "document_number" => NULL,
            ],
                        [
                "name" => "Martin Paiva",
                "address" => "Galeria San Pedro 0316 (Sotano 3)",
                "email" => NULL,
                "store_id" => 1,
                "phone" => "980268638",
                "identity_id" => 1,
                "document_number" => NULL,
            ]
        ];


        // Crea las subsecciones relacionadas a la raíz
        foreach ($suplliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
