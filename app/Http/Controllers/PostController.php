<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Services\PostService;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function index(Request $request): Response
    {
        return Inertia::render('CMS/Blog/Index', [
            'posts' => $this->posts->paginated($request),
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'rows']),
            'statusOptions' => $this->posts->formData()['statusOptions'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CMS/Blog/Create', $this->posts->formData());
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->posts->create($request);

        return redirect()->route('admin.posts.index')->with('success', 'مقاله ایجاد شد.');
    }

    public function edit(Post $post): Response
    {
        $post->load(['tags:id', 'products:id', 'category:id,name,slug']);

        return Inertia::render('CMS/Blog/Edit', [
            ...$this->posts->formData(),
            'post' => $this->posts->format($post, true),
        ]);
    }

    public function show(Post $post): RedirectResponse
    {
        return redirect()->route('admin.posts.edit', $post);
    }

    public function update(StorePostRequest $request, Post $post): RedirectResponse
    {
        $this->posts->update($request, $post);

        return redirect()->route('admin.posts.index')->with('success', 'مقاله ویرایش شد.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return back()->with('success', 'مقاله حذف شد.');
    }
}
