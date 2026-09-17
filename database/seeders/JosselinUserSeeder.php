<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JosselinUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        //===================== Creando el septimo usuario =============

        $user = User::create(
            [
                "name" => 'Josselin',
                "email" => 'josselin@3b.pe',
                "password" => bcrypt("74910587"),
                "phone" => '997081847',
                "document_number" => '74910587',
                "identity_id" => 1, //dni
                "created_at" => now(),
                "updated_at" => now(),
            ],
        );

        Employee::create(
            [
                'user_id' => $user->id,
                'salary' => 1500,
            ]
        );

        $user->assignRole(['packing']);
    }
}
