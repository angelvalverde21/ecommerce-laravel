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
            ['name' => 'Unidad(s)',     'label' => 'unidad(es)',    'singular' => 'unidad',      'plural' => 'unidades',     'abbreviation' => 'uds',  'type' => 'count',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kg(s)',         'label' => 'kilogramo(s)',  'singular' => 'kilogramo',   'plural' => 'kilogramos',   'abbreviation' => 'kg',   'type' => 'weight',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Metro(s)',      'label' => 'Metro(s)',      'singular' => 'metro',       'plural' => 'metros',       'abbreviation' => 'm',    'type' => 'length',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Docena(s)',     'label' => 'Docena(s)',     'singular' => 'docena',      'plural' => 'docenas',      'abbreviation' => 'dz',   'type' => 'count',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ciento(s)',     'label' => 'Ciento(s)',     'singular' => 'ciento',      'plural' => 'cientos',      'abbreviation' => 'ci',   'type' => 'count',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Millar(es)',     'label' => 'Millar(s)',     'singular' => 'millar',      'plural' => 'millares',     'abbreviation' => 'ml',   'type' => 'count',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Centimetro(s)', 'label' => 'Centímetro(s)', 'singular' => 'centimetro',  'plural' => 'centimetros',  'abbreviation' => 'cm',   'type' => 'length',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Corte(s)',      'label' => 'Corte',       'singular' => 'Corte',         'plural' => 'Cortes',        'abbreviation' => 'cor',  'type' => 'count',   'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
