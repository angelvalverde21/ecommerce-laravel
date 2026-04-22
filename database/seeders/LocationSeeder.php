<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $location[] = Location::create([
            'store_id' => 1,
            'name' => 'Galería la Trece',
            'parent_id' => null,
            'code' => 'GAL-01',
            'type' => 'mall',
        ]);

        $location[] = Location::create([
            'store_id' => 1,
            'name' => 'Tienda',
            'parent_id' => 1,
            'code' => '421',
            'type' => 'store',
        ]);

        $location[] = Location::create([
            'store_id' => 1,
            'name' => 'Tienda',
            'parent_id' => 1,
            'code' => '420',
            'type' => 'store',
        ]);

        $location[] = Location::create([
            'store_id' => 1,
            'name' => 'Tienda',
            'parent_id' => 1,
            'code' => '419',
            'type' => 'store',
        ]);

    }
}
