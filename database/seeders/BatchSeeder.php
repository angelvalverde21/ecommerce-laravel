<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('batches')->insert([
            [
                'id'              => 1,
                'name'            => 'Casaca Suede',
                'quantity_total'  => 126,
                'quantity_waste'  => 0,
                'production_cost' => 0.00,
                'store_id'        => 1,
                'section_id'      => 1,
                'created_at'      => '2025-08-12 06:48:15',
                'updated_at'      => '2025-08-12 22:32:22',
            ],
            [
                'id'              => 2,
                'name'            => 'palazzo de franela',
                'quantity_total'  => 99,
                'quantity_waste'  => 0,
                'production_cost' => 0.00,
                'store_id'        => 1,
                'section_id'      => 1,
                'created_at'      => '2025-08-12 06:50:24',
                'updated_at'      => '2025-08-17 10:59:32',
            ],
        ]);
    }
}
