<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlogTagRequest;
use App\Http\Requests\UpdateBlogTagRequest;
use App\Http\Resources\BlogTagResource;
use App\Models\BlogTag;
use App\Services\BlogTagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogTagController extends Controller
{
    public function __construct(private readonly BlogTagService $tags)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Blog/Tags/Index', [
            'tags' => $this->tags->paginated($request)
                ->through(fn ($tag) => BlogTagResource::make($tag)->resolve()),
            'filters' => $request->only(['search', 'rows']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Tags/Create');
    }

    public function store(StoreBlogTagRequest $request): RedirectResponse
    {
        $this->tags->create($request->validated());

        return redirect()->route('admin.blog-tags.index')->with('success', 'تگ بلاگ ایجاد شد.');
    }

    public function edit(BlogTag $blogTag): Response
    {
        return Inertia::render('Admin/Blog/Tags/Edit', [
            'tag' => BlogTagResource::make($blogTag)->resolve(),
        ]);
    }

    public function update(UpdateBlogTagRequest $request, BlogTag $blogTag): RedirectResponse
    {
        $this->tags->update($blogTag, $request->validated());

        return redirect()->route('admin.blog-tags.index')->with('success', 'تگ بلاگ ویرایش شد.');
    }

    public function destroy(BlogTag $blogTag): RedirectResponse
    {
        $blogTag->delete();

        return back()->with('success', 'تگ بلاگ حذف شد.');
    }
}
