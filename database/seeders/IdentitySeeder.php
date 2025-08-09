<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IdentitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('identities')->insert([
            [
                'code' => 'DNI',
                'name' => 'Documento Nacional de Identidad',
                'description' => 'Documento de identidad peruano',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'RUC',
                'name' => 'Registro Único de Contribuyentes',
                'description' => 'Número de identificación tributaria en Perú',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CE',
                'name' => 'Carné de Extranjería',
                'description' => 'Documento para extranjeros residentes en Perú',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PASAPORTE',
                'name' => 'Pasaporte',
                'description' => 'Documento de viaje internacional',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'OTRO',
                'name' => 'Otro',
                'description' => 'Otro tipo de documento',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
