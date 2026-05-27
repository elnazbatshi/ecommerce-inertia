<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class PublicContentController extends Controller
{
    public function products(): Response
    {
        return Inertia::render('Frontend/Products/Index');
    }

    public function product(Product $product): Response
    {
        abort_unless($product->status === 'active', 404);

        return Inertia::render('Frontend/Products/Show', [
            'product' => $product->load(['category:id,name,slug', 'brand:id,name,slug', 'images']),
        ]);
    }

    public function category(Category $category): Response
    {
        return Inertia::render('Frontend/Categories/Show', [
            'category' => $category,
        ]);
    }

    public function blog(): Response
    {
        return Inertia::render('Frontend/Blog/Index', [
            'posts' => Post::query()
                ->where('status', 'published')
                ->latest('published_at')
                ->paginate(12),
        ]);
    }

    public function post(Post $post): Response
    {
        abort_unless($post->status === 'published', 404);
        $post->increment('view_count');

        return Inertia::render('Frontend/Blog/Show', [
            'post' => $post->load(['category:id,name,slug', 'tags:id,name,slug', 'products:id,name,slug']),
        ]);
    }

    public function brand(Brand $brand): Response
    {
        return Inertia::render('Frontend/Brands/Show', [
            'brand' => $brand->load(['products:id,name,slug,brand_id,main_image,status']),
        ]);
    }

    public function page(Page $page): Response
    {
        abort_unless($page->status === 'published', 404);

        return Inertia::render('Frontend/Pages/Show', [
            'page' => $page,
        ]);
    }
}
