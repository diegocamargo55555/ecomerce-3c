<?php

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userIds = DB::table('users')->pluck('id', 'email');

        $addresses = [
            ['user' => 'test@example.com', 'recipient_name' => 'Test User', 'zip_code' => '01001000', 'street' => 'Praça da Sé', 'number' => '100', 'complement' => null, 'neighborhood' => 'Sé', 'city' => 'São Paulo', 'state' => 'SP'],
            ['user' => 'ana@example.com', 'recipient_name' => 'Ana Silva', 'zip_code' => '30130010', 'street' => 'Avenida Afonso Pena', 'number' => '200', 'complement' => 'Apartamento 301', 'neighborhood' => 'Centro', 'city' => 'Belo Horizonte', 'state' => 'MG'],
            ['user' => 'bruno@example.com', 'recipient_name' => 'Bruno Souza', 'zip_code' => '20040002', 'street' => 'Avenida Rio Branco', 'number' => '300', 'complement' => null, 'neighborhood' => 'Centro', 'city' => 'Rio de Janeiro', 'state' => 'RJ'],
        ];

        foreach ($addresses as $address) {
            Address::withTrashed()->firstOrCreate(['users_id' => $userIds[$address['user']]], [
                'users_id' => $userIds[$address['user']],
                'recipient_name' => $address['recipient_name'],
                'zip_code' => $address['zip_code'],
                'street' => $address['street'],
                'number' => $address['number'],
                'complement' => $address['complement'],
                'neighborhood' => $address['neighborhood'],
                'city' => $address['city'],
                'state' => $address['state'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
