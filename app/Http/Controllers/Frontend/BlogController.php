<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogCategoryResource;
use App\Http\Resources\BlogPostListResource;
use App\Http\Resources\Frontend\BlogPostResource;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->archive($request);
    }

    public function category(Request $request, BlogCategory $blogCategory): Response
    {
        abort_unless($blogCategory->is_active, 404);

        return $this->archive($request, $blogCategory);
    }

    public function tag(Request $request, BlogTag $blogTag): Response
    {
        return $this->archive($request, null, $blogTag);
    }

    public function show(Request $request, BlogPost $blogPost): Response
    {
        abort_unless($blogPost->newQuery()->whereKey($blogPost->id)->published()->exists(), 404);

        $blogPost->load(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug', 'products:id,name,slug,main_image,price,discount_price,stock']);
        $this->recordView($request, $blogPost);

        $relatedPosts = BlogPost::query()
            ->published()
            ->whereKeyNot($blogPost->id)
            ->with(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name'])
            ->where(function ($query) use ($blogPost) {
                $query
                    ->when($blogPost->blog_category_id, fn ($q) => $q->where('blog_category_id', $blogPost->blog_category_id))
                    ->orWhereHas('tags', fn ($q) => $q->whereIn('blog_tags.id', $blogPost->tags->pluck('id')));
            })
            ->latest('published_at')
            ->limit(6)
            ->get();

        return Inertia::render('Frontend/Blog/Show', [
            'post' => BlogPostResource::make($blogPost->refresh()->load(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug', 'products:id,name,slug,main_image,price,discount_price,stock']))->resolve(),
            'relatedPosts' => BlogPostListResource::collection($relatedPosts)->resolve(),
            'previousPost' => $this->adjacentPost($blogPost, '<'),
            'nextPost' => $this->adjacentPost($blogPost, '>'),
            'popularPosts' => BlogPostListResource::collection($this->popularPosts($blogPost->id))->resolve(),
            'categories' => BlogCategoryResource::collection($this->categories())->resolve(),
            'relatedProducts' => $blogPost->products->map(fn ($product) => $this->productCardData($product))->values(),
            'seo' => $this->seo($blogPost),
            'articleSchema' => $this->articleSchema($blogPost),
        ]);
    }

    private function archive(Request $request, ?BlogCategory $category = null, ?BlogTag $tag = null): Response
    {
        $sort = in_array($request->input('sort'), ['latest', 'oldest', 'popular'], true) ? $request->input('sort') : 'latest';
        $category ??= filled($request->input('category'))
            ? BlogCategory::query()->active()->where('slug', $request->input('category'))->first()
            : null;
        $tag ??= filled($request->input('tag'))
            ? BlogTag::query()->where('slug', $request->input('tag'))->first()
            : null;

        $posts = BlogPost::query()
            ->select(['id', 'user_id', 'blog_category_id', 'title', 'slug', 'excerpt', 'featured_image', 'featured_image_alt', 'status', 'is_featured', 'published_at', 'views', 'created_at', 'updated_at'])
            ->published()
            ->with(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name'])
            ->search($request->input('search'))
            ->when($category, fn ($query) => $query->where('blog_category_id', $category->id))
            ->when($tag, fn ($query) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereKey($tag->id)))
            ->when($sort === 'oldest', fn ($query) => $query->oldest('published_at'))
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('views'))
            ->when($sort === 'latest', fn ($query) => $query->latest('published_at'))
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Frontend/Blog/Index', [
            'posts' => $posts->through(fn ($post) => BlogPostListResource::make($post)->resolve()),
            'categories' => BlogCategoryResource::collection($this->categories())->resolve(),
            'popularPosts' => BlogPostListResource::collection($this->popularPosts())->resolve(),
            'filters' => [
                'search' => $request->input('search'),
                'category' => $category?->slug,
                'tag' => $tag?->slug,
                'sort' => $sort,
            ],
            'currentCategory' => $category ? BlogCategoryResource::make($category)->resolve() : null,
            'currentTag' => $tag ? [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ] : null,
            'seo' => [
                'title' => 'مجله موتوپارت',
                'description' => 'آخرین آموزش ها و راهنمای خرید قطعات خودرو و موتورسیکلت',
                'canonical' => route('blog.index'),
            ],
        ]);
    }

    private function categories()
    {
        return BlogCategory::query()->active()->with('children')->orderBy('sort_order')->orderBy('name')->get();
    }

    private function popularPosts(?int $except = null)
    {
        return BlogPost::query()
            ->published()
            ->when($except, fn ($query) => $query->whereKeyNot($except))
            ->with(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name'])
            ->orderByDesc('views')
            ->latest('published_at')
            ->limit(5)
            ->get();
    }

    private function adjacentPost(BlogPost $post, string $operator): ?array
    {
        $query = BlogPost::query()
            ->published()
            ->where('published_at', $operator, $post->published_at)
            ->with(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name']);

        $adjacent = $operator === '<'
            ? $query->latest('published_at')->first()
            : $query->oldest('published_at')->first();

        return $adjacent ? BlogPostListResource::make($adjacent)->resolve() : null;
    }

    private function recordView(Request $request, BlogPost $post): void
    {
        $key = "blog_post_viewed_{$post->id}";
        if (! $request->session()->has($key)) {
            $post->increment('views');
            $request->session()->put($key, true);
        }
    }

    private function seo(BlogPost $post): array
    {
        return [
            'title' => $post->meta_title ?: $post->title,
            'description' => $post->meta_description ?: $post->excerpt,
            'canonical' => $post->canonical_url ?: route('blog.show', $post),
            'image' => $post->featured_image ? \Illuminate\Support\Facades\Storage::url($post->featured_image) : null,
            'type' => 'article',
            'published_time' => $post->published_at?->toIso8601String(),
            'modified_time' => $post->updated_at?->toIso8601String(),
            'author' => $post->author?->name,
        ];
    }

    private function articleSchema(BlogPost $post): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->meta_description ?: $post->excerpt,
            'image' => $post->featured_image,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author?->name,
            ],
        ];
    }

    private function productCardData($product): array
    {
        $price = $product->discount_price ?: $product->price;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'url' => route('site.products.show', $product),
            'image' => $product->main_image ? \Illuminate\Support\Facades\Storage::url($product->main_image) : null,
            'price' => $price ? (float) $price : 0,
            'oldPrice' => $product->discount_price ? (float) $product->price : null,
            'stock' => $product->stock,
            'inStock' => $product->stock === null || $product->stock > 0,
        ];
    }
}
