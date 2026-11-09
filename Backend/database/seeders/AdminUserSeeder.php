<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('O admin de demonstração só pode ser criado em ambiente local ou de teste.');
        }

        $user = User::where('email', 'admin@example.com')->first();
        if ($user) {
            if ($user->role !== 'admin') {
                throw new RuntimeException('admin@example.com já pertence a um cliente. A conta foi preservada.');
            }

            return;
        }

        $user = new User([
            'name' => 'Admin Demo',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $user->role = 'admin';
        $user->save();
    }
}
