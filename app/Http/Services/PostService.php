<?php

namespace App\Http\Services;

use App\Http\Requests\StorePostRequest;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\Product;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PostService
{
    public function __construct(
        private readonly HtmlContentSanitizer $sanitizer,
        private readonly UploadedFileService $files,
    ) {
    }

    public function paginated(Request $request)
    {
        return Post::query()
            ->with(['category:id,name,slug', 'tags:id,name,slug'])
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query
                ->where(fn ($query) => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")))
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->input('date_from'), fn ($query, $date) => $query->whereDate('published_at', '>=', $date))
            ->when($request->input('date_to'), fn ($query, $date) => $query->whereDate('published_at', '<=', $date))
            ->latest()
            ->paginate(Pagination::perPage($request))
            ->withQueryString()
            ->through(fn (Post $post) => $this->format($post));
    }

    public function format(Post $post, bool $full = false): array
    {
        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'status' => $post->status,
            'published_at' => $post->published_at?->format('Y-m-d H:i:s'),
            'view_count' => $post->view_count,
            'category' => $post->category,
            'tags' => $post->tags,
            'featured_image' => $post->featured_image,
            'featured_image_url' => $post->featured_image ? Storage::url($post->featured_image) : null,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'meta_keywords' => $post->meta_keywords ?? [],
            'canonical_url' => $post->canonical_url,
            'seo_index' => $post->seo_index,
            'seo_follow' => $post->seo_follow,
        ];

        if ($full) {
            $data['content'] = $post->content;
            $data['post_category_id'] = $post->post_category_id;
            $data['tag_ids'] = $post->tags->pluck('id')->values();
            $data['product_ids'] = $post->products->pluck('id')->values();
        }

        return $data;
    }

    public function formData(): array
    {
        return [
            'categories' => PostCategory::query()->select(['id', 'name', 'slug'])->orderBy('name')->get(),
            'tags' => PostTag::query()->select(['id', 'name', 'slug'])->orderBy('name')->get(),
            'products' => Product::query()->select(['id', 'name', 'slug', 'sku'])->orderBy('name')->get(),
            'statusOptions' => [
                ['label' => 'پیش‌نویس', 'value' => 'draft'],
                ['label' => 'منتشر شده', 'value' => 'published'],
            ],
        ];
    }

    public function create(StorePostRequest $request): Post
    {
        return DB::transaction(function () use ($request) {
            $post = Post::create($this->payload($request));
            $this->syncRelations($post, $request);

            return $post;
        });
    }

    public function update(StorePostRequest $request, Post $post): Post
    {
        return DB::transaction(function () use ($request, $post) {
            $post->update($this->payload($request, $post));
            $this->syncRelations($post, $request);

            return $post->refresh();
        });
    }

    private function payload(StorePostRequest $request, ?Post $post = null): array
    {
        $data = $request->safe()->except(['featured_image', 'remove_featured_image', 'tag_ids', 'product_ids']);

        if ($request->boolean('remove_featured_image') && $post?->featured_image && ! $request->filled('featured_image')) {
            $this->files->delete($post->featured_image);
            $data['featured_image'] = null;
        } elseif ($request->filled('featured_image')) {
            $this->files->delete($post?->featured_image);
            $data['featured_image'] = $this->mediaPath($request->input('featured_image'));
        }

        $data['content'] = $this->sanitizer->clean($data['content'] ?? null);

        return $data;
    }

    private function syncRelations(Post $post, StorePostRequest $request): void
    {
        $post->tags()->sync($request->input('tag_ids', []));
        $post->products()->sync($request->input('product_ids', []));
    }

    private function mediaPath(int|string $mediaId): string
    {
        return Media::query()->findOrFail($mediaId)->path;
    }
}
