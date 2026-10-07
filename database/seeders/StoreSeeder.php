<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $store = Store::create([
            "id" => 1,
            "name" => "sorelle",
            "phone" => "943402809",
            "email" => "ara@3b.pe",
            "slug" => "sorelle",
            "identity_id" => "2", //2 es RUC
            "document_number" => "20478907009",
        ]);

        $store->addresses()->create([
            "primary" => "Jr Gamarra 1331",
            "secondary" => "Tienda 421",
            "name" => "Tienda 421",
            "reception_hours" => "LUNES A SABADO - 8AM A 8PM",
            "sunday_hours" => "Cerrado",
            "district_id" => 150115
        ]);
    }
}


// INSERT INTO addresses (primary, secondary, name, reception_hours, sunday_hours, district_id)
// VALUES ('Jr Gamarra 1331', 'Tienda 421', 'Tienda 421', 'LUNES A SABADO - 8AM A 8PM', 'Cerrado', 150115);