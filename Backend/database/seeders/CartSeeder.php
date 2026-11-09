<?php

namespace Database\Seeders;

use App\Models\Cart;
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
            Cart::firstOrCreate(['user_id' => $userIds[$cart['user']]], [
                'user_id' => $userIds[$cart['user']],
                'guest_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Cart::firstOrCreate(['guest_token' => self::guestToken()], ['user_id' => null]);
    }

    public static function guestToken(): string
    {
        return hash_hmac('sha256', 'seeder-guest-cart', config('app.key'));
    }
}
