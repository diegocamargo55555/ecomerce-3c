<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Database\Eloquent\Collection;

class AddressService
{
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

    public function update(int $id, array $data): Address
    {
        $address = Address::findOrFail($id);
        $address->update($data);

        return $address;
    }

    public function delete(int $id): void
    {
        Address::findOrFail($id)->delete();
    }
}