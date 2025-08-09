<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $json = File::get("database/data/departments.json");

        $data = json_decode($json);

        foreach ($data as $obj) {

            Department::create(

                [
                    'id' => $obj->IDDEPARTAMENTO,
                    'name' => $obj->NOMBREDEPARTAMENTO
                ]

            );
        }
    }
}
