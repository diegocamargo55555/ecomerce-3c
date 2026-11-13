<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
        DB::transaction(function () use ($id) {
            $category = Category::lockForUpdate()->findOrFail($id);
            $products = $category->products()->count();
            abort_if(
                $products > 0,
                409,
                "Não é possível excluir a categoria {$category->name}: há {$products} produto(s) vinculado(s). Mova ou exclua os produtos antes."
            );
            $category->delete();
        });
    }
}
