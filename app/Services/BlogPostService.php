<?php

namespace App\Services;

use App\Http\Services\HtmlContentSanitizer;
use App\Http\Services\SlugService;
use App\Http\Services\UploadedFileService;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Media;
use App\Models\Product;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BlogPostService
{
    public function __construct(
        private readonly SlugService $slugService,
        private readonly HtmlContentSanitizer $sanitizer,
        private readonly UploadedFileService $files,
    ) {
    }

    public function paginated(Request $request)
    {
        return BlogPost::query()
            ->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->filter($request->only(['search', 'status', 'blog_category_id', 'is_featured', 'sort_field', 'sort_order']))
            ->paginate($this->perPage($request))
            ->withQueryString();
    }

    public function formData(): array
    {
        return [
            'categories' => BlogCategory::query()->select(['id', 'name', 'slug', 'parent_id'])->orderBy('sort_order')->orderBy('name')->get(),
            'tags' => BlogTag::query()->select(['id', 'name', 'slug'])->orderBy('name')->get(),
            'products' => Product::query()->select(['id', 'name', 'slug', 'main_image'])->orderBy('name')->limit(100)->get(),
            'statuses' => BlogPost::STATUSES,
        ];
    }

    public function create(array $data, int $userId): BlogPost
    {
        return DB::transaction(function () use ($data, $userId) {
            $post = BlogPost::create($this->payload($data, $userId));
            $this->syncRelations($post, $data);

            return $post->refresh();
        });
    }

    public function update(BlogPost $post, array $data): BlogPost
    {
        return DB::transaction(function () use ($post, $data) {
            $post->update($this->payload($data, $post->user_id, $post));
            $this->syncRelations($post, $data);

            return $post->refresh();
        });
    }

    public function delete(BlogPost $post): void
    {
        DB::transaction(function () use ($post) {
            $post->tags()->detach();
            $post->products()->detach();
            $this->files->delete($post->featured_image);
            $post->delete();
        });
    }

    public function toggleFeatured(BlogPost $post, bool $featured): BlogPost
    {
        $post->update(['is_featured' => $featured]);

        return $post->refresh();
    }

    private function payload(array $data, int $userId, ?BlogPost $post = null): array
    {
        $payload = collect($data)->only([
            'blog_category_id',
            'title',
            'excerpt',
            'content',
            'featured_image_alt',
            'status',
            'is_featured',
            'published_at',
            'meta_title',
            'meta_description',
            'canonical_url',
        ])->all();

        $payload['user_id'] = $userId;
        $payload['content'] = $this->sanitizer->clean($payload['content'] ?? '');
        $payload['is_featured'] = (bool) ($payload['is_featured'] ?? false);

        if (! $post || filled($data['slug'] ?? null)) {
            $payload['slug'] = $this->slugService->unique(BlogPost::class, $data['slug'] ?? $data['title'], $post?->id);
        }

        if (($payload['status'] ?? null) === BlogPost::STATUS_PUBLISHED && blank($payload['published_at'] ?? null)) {
            $payload['published_at'] = now();
        }

        if (array_key_exists('featured_image', $data) && filled($data['featured_image'])) {
            $payload['featured_image'] = $this->resolveImage($data['featured_image'], $post?->featured_image);
        }

        return $payload;
    }

    private function syncRelations(BlogPost $post, array $data): void
    {
        $post->tags()->sync($data['tag_ids'] ?? []);
        $products = collect($data['product_ids'] ?? [])
            ->values()
            ->mapWithKeys(fn ($id, int $index) => [(int) $id => ['sort_order' => $index]])
            ->all();

        $post->products()->sync($products);
    }

    private function resolveImage(mixed $image, ?string $currentPath): string
    {
        if ($image instanceof UploadedFile) {
            return $this->files->replace($currentPath, $image, 'blog/posts');
        }

        if (is_numeric($image)) {
            return Media::query()->findOrFail((int) $image)->path;
        }

        return (string) $image;
    }

    private function perPage(Request $request): int
    {
        $rows = (int) $request->input('rows', 20);

        return in_array($rows, [10, 20, 30, 50, 100], true) ? $rows : 20;
    }
}
