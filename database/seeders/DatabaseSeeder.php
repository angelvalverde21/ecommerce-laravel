<?php

namespace Database\Seeders;


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
        $this->call(UnitSeeder::class);
        
        $this->call(UserSeeder::class);
        $this->call(StoreSeeder::class);
        $this->call(StoreUserSeeder::class);

        $this->call(SupplierSeeder::class);
        // $this->call(PurchaseSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(ProvinceSeeder::class);
        $this->call(DistrictSeeder::class);
    }
}
