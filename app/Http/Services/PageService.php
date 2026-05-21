<?php

namespace App\Http\Services;

use App\Http\Requests\StorePageRequest;
use App\Models\Media;
use App\Models\Page;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PageService
{
    public function __construct(
        private readonly HtmlContentSanitizer $sanitizer,
        private readonly UploadedFileService $files,
    ) {
    }

    public function paginated(Request $request)
    {
        return Page::query()
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
            ->through(fn (Page $page) => $this->format($page));
    }

    public function format(Page $page, bool $full = false): array
    {
        $data = [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'status' => $page->status,
            'published_at' => $page->published_at?->format('Y-m-d H:i:s'),
            'template' => $page->template,
            'featured_image' => $page->featured_image,
            'featured_image_url' => $page->featured_image ? Storage::url($page->featured_image) : null,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords ?? [],
            'canonical_url' => $page->canonical_url,
            'seo_index' => $page->seo_index,
            'seo_follow' => $page->seo_follow,
        ];

        if ($full) {
            $data['content'] = $page->content;
        }

        return $data;
    }

    public function formData(): array
    {
        return [
            'statusOptions' => [
                ['label' => 'پیش‌نویس', 'value' => 'draft'],
                ['label' => 'منتشر شده', 'value' => 'published'],
            ],
            'templateOptions' => [
                ['label' => 'پیش‌فرض', 'value' => null],
                ['label' => 'درباره ما', 'value' => 'about'],
                ['label' => 'تماس با ما', 'value' => 'contact'],
                ['label' => 'پرسش‌های متداول', 'value' => 'faq'],
            ],
        ];
    }

    public function create(StorePageRequest $request): Page
    {
        return Page::create($this->payload($request));
    }

    public function update(StorePageRequest $request, Page $page): Page
    {
        $page->update($this->payload($request, $page));

        return $page->refresh();
    }

    private function payload(StorePageRequest $request, ?Page $page = null): array
    {
        $data = $request->safe()->except(['featured_image', 'remove_featured_image']);

        if ($request->boolean('remove_featured_image') && $page?->featured_image && ! $request->filled('featured_image')) {
            $this->files->delete($page->featured_image);
            $data['featured_image'] = null;
        } elseif ($request->filled('featured_image')) {
            $this->files->delete($page?->featured_image);
            $data['featured_image'] = $this->mediaPath($request->input('featured_image'));
        }

        $data['content'] = $this->sanitizer->clean($data['content'] ?? null);

        return $data;
    }

    private function mediaPath(int|string $mediaId): string
    {
        return Media::query()->findOrFail($mediaId)->path;
    }
}
