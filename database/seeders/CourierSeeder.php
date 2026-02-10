<?php

namespace Database\Seeders;

use App\Models\Courier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CourierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //GAMA EXPRESS. SHALOM, INDRIVER, OLVA COURIER, DINSIDES

        $shalom =
            [
                'name' => 'SHALOM',
                'phone' => '015007878',
                'is_cash_on_delivery' => 0, //Acepta Pago contra entrega
                'is_freight_collect' => 1, //Acepta cobrar los costos de envio en destino, osea pago en destino
                'is_express_shipping' => 0, //No acepta envios express, osea envios rapidos
                'email' => 'ventas@shalom.com.pe',
                'identity_id' => 2, // Asumiendo que el courier es una persona
                'document_number' => mt_rand(20000000000, 99999999999),

            ];

        $couriers = [
            [
                'name' => 'GAMA EXPRESS',
                'phone' => '925778997',
                'is_cash_on_delivery' => 1, //Acepta Pago contra entrega
                'is_freight_collect' => 1, //Acepta cobrar los costos de envio en destino, osea pago en destino
                'is_express_shipping' => 1, //Acepta envios express, osea envios rapidos
                'email' => 'gamaexpressperu@gmail.com',
                'identity_id' => 2, // Asumiendo que el courier es una persona natural y tiene una identidad asociada
                'document_number' => mt_rand(20000000000, 99999999999),
                'addresses' => [
                    [
                        'name' => 'GAMA EXPRESS S.A.C.',
                        'primary' => 'Galeria Gama',
                        'secondary' => null,
                        'district_id' => 150115
                    ]
                ],

            ],

            [
                'name' => 'INDRIVER PERU S.A.C.',
                'phone' => '015007878',
                'is_cash_on_delivery' => 0, //No acepta Pago contra entrega
                'is_freight_collect' => 1, //Acepta cobrar los costos de envio en destino, osea pago en destino
                'is_express_shipping' => 1, //Acepta envios express, osea envios rapidos
                'email' => 'support@indrive.com',
                'identity_id' => 2, // Asumiendo que el courier es una persona
                'document_number' => mt_rand(20000000000, 99999999999),
                'addresses' => [
                    [
                        'name' => 'MOTORIZADO',
                        'primary' => 'LIMA',
                        'secondary' => null,
                        'district_id' => 150115
                    ],
                    [
                        'name' => 'AUTO',
                        'primary' => 'LIMA',
                        'secondary' => null,
                        'district_id' => 150115
                    ],
                ],
            ],
            [
                'name' => 'OLVA COURIER',
                'phone' => '017140909',
                'is_cash_on_delivery' => 0, //Acepta Pago contra entrega
                'is_freight_collect' => 0, //No acepta cobrar los costos de envio en destino, osea pago en destino
                'is_express_shipping' => 0, //No acepta envios express, osea envios rapidos
                'email' => 'atencionalcliente@olva.com.pe',
                'identity_id' => 2, // Asumiendo que el courier es una persona
                'document_number' => mt_rand(20000000000, 99999999999),
                'addresses' => [
                    [
                        'name' => 'OLVA GAMARRA',
                        'primary' => 'Jr. Antonio Bazo 1280',
                        'phone' => '017140909',
                        'secondary' => null,
                        'district_id' => 150115
                    ]
                ],
            ],
            [
                'name' => 'DINSIDES',
                'phone' => '989424937',
                'is_cash_on_delivery' => 1, //Acepta Pago contra entrega
                'is_freight_collect' => 0, //No acepta cobrar los costos de envio en destino, osea pago en destino
                'is_express_shipping' => 1, //Acepta envios express, osea envios rapidos
                'email' => 'contacto@dinsidescourier.com',
                'identity_id' => 2, // Asumiendo que el courier es una persona
                'document_number' => mt_rand(20000000000, 99999999999),
                'addresses' => [
                    [
                        'name' => 'DINSIDES GAMARRA',
                        'primary' => 'Jr. Antonio Bazo 1218',
                        'phone' => '989424937',
                        'secondary' => null,
                        'district_id' => 150115
                    ]
                ],
            ]
        ];

        foreach ($couriers as $courier) {

            $this->command->info('Se ejecuta el seeder de courier: ' . $courier['name']);

            $user = User::create(
                [
                    'name' => $courier['name'],
                    'email' => $courier['email'],
                    'phone' => $courier['phone'],
                    'document_number' => $courier['document_number'],
                    'identity_id' => $courier['identity_id'],
                    'password' => bcrypt(md5($courier['phone'])), // Default password
                ]
            );

            $user->stores()->attach(1); // Asignar el courier a la tienda con ID 1

            $courierResp = $user->courier()->create([
                'is_cash_on_delivery' => $courier['is_cash_on_delivery'],
                'is_freight_collect' => $courier['is_freight_collect'],
                'is_express_shipping' => $courier['is_express_shipping']
            ]);

            foreach ($courier['addresses'] as $address) {
                //  $this->command->info('Se ejecuta el seeder de courier: ' . $address['name']);
                $courierResp->addresses()->create($address);
            }
        }

        //Ahora empezamos a crear las agencias de shalom y cada una es una direccion diferente pero todas pertenecen al mismo courier

        $user = User::create([
            'name' => $shalom['name'],
            'email' => $shalom['email'],
            'phone' => $shalom['phone'],
            'document_number' => $shalom['document_number'],
            'identity_id' => $shalom['identity_id'],
            'password' => bcrypt(md5($shalom['phone'])), // Default password
        ]);

        $user->stores()->attach(1); // Asignar el courier a la tienda con ID 1


        $courier = $user->courier()->create([
            'is_cash_on_delivery' => $shalom['is_cash_on_delivery'],
            'is_freight_collect' => $shalom['is_freight_collect'],
            'is_express_shipping' => $shalom['is_express_shipping']
        ]);

        $data = File::get("database/data/agencias_shalom.json");

        $addresses_shalom = json_decode($data);

        $order = 0;

        foreach ($addresses_shalom as $address) {

            $order++;

            $courier->addresses()->create([
                'name' => $address->lugar_over,
                'identity_id' => null,
                'document_number' => null,
                'phone' => $address->telefono ?? null,
                'type' => 'oficina',
                'primary' => $address->direccion,
                'secondary' => $address->name ?? null,
                'references' => $address->name ?? null,
                'latitud' => $address->latitud,
                'longitud' => $address->longitud,
                'url_maps' => "https://www.google.com/maps/search/?api=1&query={$address->latitud},{$address->longitud}",
                'reception_hours' => $address->hora_atencion,
                'sunday_hours' => $address->hora_domingo,
                'status' => 1,
                'is_default' => 0,
                'district_id' => $address->ubi_id,
                'sort_order' => $order,

            ]);
        }
    }
}
