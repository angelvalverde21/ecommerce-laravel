<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $json = File::get("database/data/districts.json");

        $data = json_decode($json);

        foreach ($data as $obj) {

            District::create(

                [
                    'id' => $obj->IDDISTRITO,
                    'name' => $obj->NOMBREDISTRITO,
                    'province_id' => $obj->IDPROVINCIA
                ]

            );
        }
    }
}
