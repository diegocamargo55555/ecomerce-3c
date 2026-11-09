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

    public function listAll(): Collection
    {
        return Address::all();
    }

    public function create(array $data): Address
    {
        return Address::create($data);
    }

    public function findById(int $id): Address
    {
        return Address::findOrFail($id);
    }

    public function update(int $id, array $data, ?int $userId = null): Address
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $address = Address::when($userId !== null, fn ($query) => $query->where('users_id', $userId))
                ->lockForUpdate()->findOrFail($id);
            $address->fill($data);
            if ($address->isDirty() && $address->orders()->exists()) {
                // Mantém o endereço original dos pedidos e oferece uma nova versão para entregas.
                $replacement = $address->replicate();
                $replacement->save();
                $address->refresh()->delete();

                return $replacement;
            }
            $address->save();

            return $address;
        }, 3);
    }

    public function delete(int $id, ?int $userId = null): void
    {
        DB::transaction(function () use ($id, $userId) {
            Address::when($userId !== null, fn ($query) => $query->where('users_id', $userId))
                ->lockForUpdate()->findOrFail($id)->delete();
        }, 3);
    }
}
