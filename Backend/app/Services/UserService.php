<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    public function listAll(): Collection
    {
        return User::all();
    }

    public function findById(int $id): User
    {
        return User::findOrFail($id);
    }
}