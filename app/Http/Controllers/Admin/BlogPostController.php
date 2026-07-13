<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlogPostRequest;
use App\Http\Requests\ToggleBlogPostFeaturedRequest;
use App\Http\Requests\UpdateBlogPostRequest;
use App\Http\Resources\BlogPostListResource;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Services\BlogPostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogPostController extends Controller
{
    public function __construct(private readonly BlogPostService $posts)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Blog/Posts/Index', [
            'posts' => $this->posts->paginated($request)
                ->through(fn ($post) => BlogPostListResource::make($post)->resolve()),
            'filters' => $request->only(['search', 'status', 'blog_category_id', 'is_featured', 'sort_field', 'sort_order', 'rows']),
            ...$this->posts->formData(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Posts/Create', $this->posts->formData());
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $this->posts->create($request->validated(), $request->user()->id);

        return redirect()->route('admin.blog-posts.index')->with('success', 'مقاله بلاگ ایجاد شد.');
    }

    public function edit(BlogPost $blogPost): Response
    {
        return Inertia::render('Admin/Blog/Posts/Edit', [
            ...$this->posts->formData(),
            'post' => BlogPostResource::make($blogPost->load(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug', 'products:id,name,slug,main_image']))->resolve(),
        ]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $this->posts->update($blogPost, $request->validated());

        return redirect()->route('admin.blog-posts.index')->with('success', 'مقاله بلاگ ویرایش شد.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $this->posts->delete($blogPost);

        return back()->with('success', 'مقاله بلاگ حذف شد.');
    }

    public function toggleFeatured(ToggleBlogPostFeaturedRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $this->posts->toggleFeatured($blogPost, $request->boolean('is_featured'));

        return back()->with('success', 'وضعیت ویژه بودن مقاله تغییر کرد.');
    }
}
