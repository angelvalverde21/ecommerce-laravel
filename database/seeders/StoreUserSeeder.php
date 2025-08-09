<?php

namespace Database\Seeders;

use App\Models\StoreUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
                StoreUser::insert(
            [

                [
                    "id" => 1,
                    "user_id" => 1,
                    "store_id" => 1,
                    "created_at" => now(),
                    "updated_at" => now(),
                ],

            ]
        );
    }
}
