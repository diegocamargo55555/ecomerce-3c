<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function listAll(): Collection
    {
        // A categoria não tem foto própria: usa a do primeiro produto ativo com foto.
        return Category::addSelect(['image_url' => Product::select('image_url')
            ->whereColumn('category_id', 'categories.id')
            ->where('is_active', true)
            ->whereNotNull('image_url')
            ->orderBy('id')
            ->limit(1),
        ])->get();
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
            // Produtos excluídos logicamente continuam no banco e nos pedidos antigos,
            // então também impedem a exclusão.
            $products = $category->products()->withTrashed()->count();
            abort_if(
                $products > 0,
                409,
                "Não é possível excluir a categoria {$category->name}: há {$products} produto(s) vinculado(s). Mova ou exclua os produtos antes."
            );
            $category->delete();
        });
    }
}
