<?php

namespace Database\Seeders;

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
    public function run(): void{

        User::insert(
            [

                [
                    "id" => 1,
                    "name" => 'Angel Valverde',
                    "email" => 'angelvalverde21@gmail.com',
                    "password" => bcrypt("12345678"),
                    "phone" => '943402809',
                    "document_number" => '42412498',
                    "identity_id" => 1,
                    "created_at" => now(),
                    "updated_at" => now(),
                ],
                [
                    "id" => 2,
                    "name" => 'Vanesa',
                    "email" => 'vanesahg@gmail.com',
                    "password" => bcrypt("12345678"),
                    "phone" => '945101774',
                    "document_number" => '45631639',
                    "identity_id" => 1,
                    "created_at" => now(),
                    "updated_at" => now(),
                ],
            ]
        );
    }

}
