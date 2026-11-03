<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userIds = DB::table('users')->pluck('id', 'email');

        $carts = [
            ['user' => 'test@example.com'],
            ['user' => 'ana@example.com'],
            ['user' => 'bruno@example.com'],
        ];

        foreach ($carts as $cart) {
            DB::table('carts')->insert([
                'user_id' => $userIds[$cart['user']],
                'guest_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
