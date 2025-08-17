<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $groupsModelBatch = [
            'Patrones',
            'Muestras',
            'Compra de tela',
            'Corte', // o 'Garments'
            'Confeccion', // o 'Garments'
            'Others',
        ];

        

    }
}
