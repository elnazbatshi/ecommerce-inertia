<?php

namespace App\Services;

use App\Http\Services\SlugService;
use App\Models\BlogTag;
use App\Support\Pagination;
use Illuminate\Http\Request;

class BlogTagService
{
    public function __construct(private readonly SlugService $slugService)
    {
    }

    public function paginated(Request $request)
    {
        return BlogTag::query()
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(Pagination::perPage($request))
            ->withQueryString();
    }

    public function create(array $data): BlogTag
    {
        $data['slug'] = $this->slugService->unique(BlogTag::class, $data['slug'] ?? $data['name']);

        return BlogTag::create($data);
    }

    public function update(BlogTag $tag, array $data): BlogTag
    {
        if (filled($data['slug'] ?? null)) {
            $data['slug'] = $this->slugService->unique(BlogTag::class, $data['slug'], $tag->id);
        } else {
            unset($data['slug']);
        }

        $tag->update($data);

        return $tag->refresh();
    }
}
