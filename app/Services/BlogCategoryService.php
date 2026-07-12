<?php

namespace App\Services;

use App\Http\Services\SlugService;
use App\Models\BlogCategory;
use App\Support\Pagination;
use Illuminate\Http\Request;

class BlogCategoryService
{
    public function __construct(private readonly SlugService $slugService)
    {
    }

    public function paginated(Request $request)
    {
        return BlogCategory::query()
            ->with('parent:id,name,slug')
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(Pagination::perPage($request))
            ->withQueryString();
    }

    public function create(array $data): BlogCategory
    {
        $data['slug'] = $this->slugService->unique(BlogCategory::class, $data['slug'] ?? $data['name']);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return BlogCategory::create($data);
    }

    public function update(BlogCategory $category, array $data): BlogCategory
    {
        if (filled($data['slug'] ?? null)) {
            $data['slug'] = $this->slugService->unique(BlogCategory::class, $data['slug'], $category->id);
        } else {
            unset($data['slug']);
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $category->update($data);

        return $category->refresh();
    }
}
