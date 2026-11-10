<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function listAll(): Collection
    {
        return User::all();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findById(int $id): User
    {
        return User::findOrFail($id);
    }

    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);

        return $user;
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $user = User::lockForUpdate()->findOrFail($id);
            // Pedidos guardam o histórico de vendas e referenciam a conta e seus endereços.
            abort_if(
                $user->orders()->exists(),
                409,
                "Não é possível excluir {$user->name}: há pedidos registrados nesta conta."
            );
            $user->carts()->delete();
            $user->delete();
        });
    }
}