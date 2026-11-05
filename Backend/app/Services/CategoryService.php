<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function listAll(): Collection
    {
        return Category::all();
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function findById(int $id): Category
    {
        return Category::findOrFail($id);
    }

    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);
        $category->update($data);

        return $category;
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);
        if ($category->products()->withTrashed()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'category' => 'Esta categoria possui produtos vinculados e não pode ser excluída.',
            ]);
        }
        $category->delete();
    }
}
