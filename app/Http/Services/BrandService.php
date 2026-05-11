<?php

namespace App\Http\Services;

use App\Http\Requests\StoreBrandRequest;
use App\Models\Brand;
use App\Models\Media;

class BrandService
{
    public function __construct(
        private readonly HtmlContentSanitizer $sanitizer,
        private readonly UploadedFileService $files,
    ) {
    }

    public function all()
    {
        return Brand::query()->orderBy('name')->get();
    }

    public function create(StoreBrandRequest $request): Brand
    {
        return Brand::create($this->payload($request));
    }

    public function update(StoreBrandRequest $request, Brand $brand): Brand
    {
        $brand->update($this->payload($request, $brand));

        return $brand->refresh();
    }

    public function delete(Brand $brand): void
    {
        foreach (['logo', 'featured_image', 'cover_image'] as $field) {
            $this->files->delete($brand->{$field});
        }

        $brand->delete();
    }

    private function payload(StoreBrandRequest $request, ?Brand $brand = null): array
    {
        $data = $request->safe()->except(['remove_logo', 'remove_featured_image', 'remove_cover_image']);

        foreach (['logo', 'featured_image', 'cover_image'] as $field) {
            $removeField = 'remove_'.$field;

            if ($request->boolean($removeField) && $brand?->{$field} && ! $request->filled($field)) {
                $this->files->delete($brand->{$field});
                $data[$field] = null;
            } elseif ($request->filled($field)) {
                $this->files->delete($brand?->{$field});
                $data[$field] = $this->mediaPath($request->input($field));
            } elseif ($brand) {
                unset($data[$field]);
            }
        }

        $data['content'] = $this->sanitizer->clean($data['content'] ?? null);

        return $data;
    }

    private function mediaPath(int|string $mediaId): string
    {
        return Media::query()->findOrFail($mediaId)->path;
    }
}
