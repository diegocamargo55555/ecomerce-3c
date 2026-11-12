<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function listForUser(int $userId): Collection
    {
        return Address::where('users_id', $userId)->orderByDesc('id')->get();
    }

    public function create(array $data): Address
    {
        return Address::create($data);
    }

    public function update(int $id, array $data, ?int $userId = null): Address
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $address = Address::when($userId !== null, fn ($query) => $query->where('users_id', $userId))
                ->lockForUpdate()->findOrFail($id);
            $address->update($data);

            return $address;
        }, 3);
    }

    public function delete(int $id, ?int $userId = null): void
    {
        DB::transaction(function () use ($id, $userId) {
            $address = Address::when($userId !== null, fn ($query) => $query->where('users_id', $userId))
                ->lockForUpdate()->findOrFail($id);
            // Pedidos guardam address_id: endereço já usado em pedido não pode ser apagado.
            abort_if($address->orders()->exists(), 409, 'Não é possível excluir este endereço: há pedidos vinculados a ele.');
            $address->delete();
        }, 3);
    }
}
