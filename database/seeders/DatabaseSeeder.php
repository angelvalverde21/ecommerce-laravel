<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
 
        $this->call(IdentitySeeder::class);
        $this->call(UserSeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(StoreSeeder::class);
        $this->call(StoreUserSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(ProvinceSeeder::class);
        $this->call(DistrictSeeder::class);
    }
}
