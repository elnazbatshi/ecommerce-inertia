<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Services\CatalogService;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Categories/Index', [
            'categories' => $this->catalog->categories(),
            'categoryOptions' => $this->catalog->categoryOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return back()->with('success', 'دسته‌بندی ایجاد شد.');
    }

    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return back()->with('success', 'دسته‌بندی ویرایش شد.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'دسته‌بندی حذف شد.');
    }
}
