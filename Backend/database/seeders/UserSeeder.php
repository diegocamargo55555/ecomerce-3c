<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password');

        $users = [
            ['name' => 'Test User', 'email' => 'test@example.com'],
            ['name' => 'Ana Silva', 'email' => 'ana@example.com'],
            ['name' => 'Bruno Souza', 'email' => 'bruno@example.com'],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert([
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified_at' => $now,
                'password' => $password,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
