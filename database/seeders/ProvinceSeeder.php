<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = File::get("database/data/provinces.json");

        $data = json_decode($json);

        foreach ($data as $obj) {

            Province::create(

                [
                    'id' => $obj->IDPROVINCIA,
                    'name' => $obj->NOMBREPROVINCIA,
                    'department_id' => $obj->IDDEPARTAMENTO
                ]

            );
        }
    }
}
