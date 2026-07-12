<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlogCategoryRequest;
use App\Http\Requests\UpdateBlogCategoryRequest;
use App\Http\Resources\BlogCategoryResource;
use App\Models\BlogCategory;
use App\Services\BlogCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogCategoryController extends Controller
{
    public function __construct(private readonly BlogCategoryService $categories)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Blog/Categories/Index', [
            'categories' => $this->categories->paginated($request)
                ->through(fn ($category) => BlogCategoryResource::make($category)->resolve()),
            'filters' => $request->only(['search', 'rows']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Categories/Create', $this->formData());
    }

    public function store(StoreBlogCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return redirect()->route('admin.blog-categories.index')->with('success', 'دسته بندی بلاگ ایجاد شد.');
    }

    public function edit(BlogCategory $blogCategory): Response
    {
        return Inertia::render('Admin/Blog/Categories/Edit', [
            ...$this->formData(),
            'category' => BlogCategoryResource::make($blogCategory->load('parent'))->resolve(),
        ]);
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $blogCategory): RedirectResponse
    {
        $this->categories->update($blogCategory, $request->validated());

        return redirect()->route('admin.blog-categories.index')->with('success', 'دسته بندی بلاگ ویرایش شد.');
    }

    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        $blogCategory->delete();

        return back()->with('success', 'دسته بندی بلاگ حذف شد.');
    }

    private function formData(): array
    {
        return [
            'categories' => BlogCategory::query()->select(['id', 'name', 'slug', 'parent_id'])->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }
}
