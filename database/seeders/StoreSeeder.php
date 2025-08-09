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
        Store::insert([
            [
                "id" => 1,
                "name" => "sorelle",
                "phone" => "943402809",
                "email" => "sorelle@3b.pe",
                "slug" => "sorelle",
                "identity_id" => "2", //2 es RUC
                "document_number" => "20478907009",

            ],
        ]);
    }
}
