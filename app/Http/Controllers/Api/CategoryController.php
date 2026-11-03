<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    public function index()
    {
        return response()->json($this->categoryService->listAll());
    }

    public function store(Request $request)
    {
        $category = $this->categoryService->create($request->all());

        return response()->json($category, 201);
    }

    public function show(string $id)
    {
        return response()->json($this->categoryService->findById($id));
    }

    public function update(Request $request, string $id)
    {
        $category = $this->categoryService->update($id, $request->all());

        return response()->json($category);
    }

    public function destroy(string $id)
    {
        $this->categoryService->delete($id);

        return response()->noContent();
    }
}
