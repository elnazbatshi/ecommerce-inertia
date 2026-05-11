<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostCategoryRequest;
use App\Http\Services\CatalogService;
use App\Models\PostCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostCategoryController extends Controller
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    public function index(): Response
    {
        return Inertia::render('CMS/Categories/Index', [
            'categories' => $this->catalog->postCategories(),
        ]);
    }

    public function store(StorePostCategoryRequest $request): RedirectResponse
    {
        PostCategory::create($request->validated());

        return back()->with('success', 'دسته‌بندی ایجاد شد.');
    }

    public function update(StorePostCategoryRequest $request, PostCategory $postCategory): RedirectResponse
    {
        $postCategory->update($request->validated());

        return back()->with('success', 'دسته‌بندی ویرایش شد.');
    }

    public function destroy(PostCategory $postCategory): RedirectResponse
    {
        $postCategory->delete();

        return back()->with('success', 'دسته‌بندی حذف شد.');
    }
}
