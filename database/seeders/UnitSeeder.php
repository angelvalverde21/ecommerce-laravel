<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('units')->insert([
            ['name' => 'unidad(es)',         'abbreviation' => 'uds',  'type' => 'count',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'metro(s)',           'abbreviation' => 'm',    'type' => 'length',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'docena(s)',          'abbreviation' => 'dz',   'type' => 'count',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'centímetro(s)',      'abbreviation' => 'cm',   'type' => 'length',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'milímetro(s)',       'abbreviation' => 'mm',   'type' => 'length',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'kilogramo(s)',       'abbreviation' => 'kg',   'type' => 'weight',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'gramo(s)',           'abbreviation' => 'g',    'type' => 'weight',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'litro(s)',           'abbreviation' => 'l',    'type' => 'volume',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'mililitro(s)',       'abbreviation' => 'ml',   'type' => 'volume',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'par(es)',            'abbreviation' => 'prs',  'type' => 'count',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'juego(s)',           'abbreviation' => 'jgo',  'type' => 'count',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'metro(s) cuadrado(s)','abbreviation' => 'm2',   'type' => 'area',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'metro(s) cúbico(s)', 'abbreviation' => 'm3',   'type' => 'volume',  'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
