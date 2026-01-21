<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        //====================== Creando el primer usuarios ============

        $user = User::create(
            [
                "id" => 1,
                "name" => 'Angel Valverde',
                "email" => 'angelvalverde21@gmail.com',
                "password" => bcrypt("12345678"),
                "phone" => '943402809',
                "document_number" => '42412498',
                "identity_id" => 1, //dni
                "created_at" => now(),
                "updated_at" => now(),
            ],
        );

        //asignar su rol

        $user->assignRole(['master']);

        //===================== Creando el segundo usuario =============

        $user = User::create(
            [
                "id" => 2,
                "name" => 'Vanesa',
                "email" => 'vanesahg@gmail.com',
                "password" => bcrypt("12345678"),
                "phone" => '945101774',
                "document_number" => '45631639',
                "identity_id" => 1, //dni
                "created_at" => now(),
                "updated_at" => now(),
            ],
        );

        //Asignar su rol

        $user->assignRole(['master', 'ceo']);

        //Asignarlo como empleado

        Employee::create(
            [
                'user_id' => $user->id,
                'salary' => 1500,
            ]
        );

        //===================== Creando el tercer usuario =============

        $user = User::create(
            [
                "id" => 3,
                "name" => 'Jennifer',
                "email" => 'jeni@3b.pe',
                "password" => bcrypt("12345678"),
                "phone" => '904086292',
                "document_number" => '74641638',
                "identity_id" => 1, //dni
                "created_at" => now(),
                "updated_at" => now(),
            ],
        );

        $user->assignRole(['sales']);


        Employee::create(
            [
                'user_id' => $user->id,
                'salary' => 1500,
            ]
        );

        //===================== Creando el cuarto usuario =============

        $user = User::create(
            [
                "id" => 4,
                "name" => 'Aylin',
                "email" => 'aylin@3b.pe',
                "password" => bcrypt("12345678"),
                "phone" => '000000000',
                "document_number" => '00000000',
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

        $user->assignRole(['sales']);

        //===================== Creando el Quinto usuario =============

        $user = User::create(
            [
                "id" => 5,
                "name" => 'Pamela',
                "email" => 'sorelle@3b.pe',
                "password" => bcrypt("76935223"),
                "phone" => '000000000',
                "document_number" => '927463297',
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

        $user->assignRole(['ceo']);
    }
}
