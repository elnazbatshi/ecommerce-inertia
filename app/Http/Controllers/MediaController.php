<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachMediaRequest;
use App\Http\Requests\BulkDeleteMediaRequest;
use App\Http\Requests\DetachMediaRequest;
use App\Http\Requests\ReorderMediaRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $query = Media::query();

        if ($request->filled('ids')) {
            $query->whereIn('id', array_filter((array) $request->input('ids')));
        }

        if ($request->filled('search')) {
            $term = $request->string('search')->trim();
            $query->where(function ($query) use ($term) {
                $query->where('original_name', 'like', "%{$term}%")
                    ->orWhere('filename', 'like', "%{$term}%")
                    ->orWhere('alt', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%");
            });
        }

        if ($request->filled('mime_type')) {
            $type = $request->string('mime_type')->toString();
            $query->where('mime_type', 'like', "{$type}/%");
        }

        $media = $query->latest()->paginate($request->integer('rows', 24))->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['media' => $media]);
        }

        return Inertia::render('Media/Index', [
            'media' => $media,
            'filters' => $request->only(['search', 'rows']),
        ]);
    }

    public function upload(StoreMediaRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $this->storeFile($file);
        $dimensions = getimagesize($file->getPathname());

        $media = Media::create([
            'disk' => $this->disk(),
            'path' => $path,
            'filename' => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize() ?: 0,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'alt' => $request->input('alt'),
            'title' => $request->input('title'),
            'caption' => $request->input('caption'),
            'description' => $request->input('description'),
            'metadata' => $request->input('metadata') ? json_decode($request->input('metadata'), true) : null,
            'uploaded_by' => $request->user()?->id,
        ]);

        return response()->json(['media' => $media]);
    }

    public function update(UpdateMediaRequest $request, Media $media): JsonResponse
    {
        $media->update($request->validated());

        return response()->json(['media' => $media]);
    }

    public function destroy(Media $media): JsonResponse
    {
        if ($this->hasAttachments($media)) {
            return response()->json([
                'message' => 'این رسانه هنوز در جایی استفاده می‌شود. ابتدا آن را از موارد مرتبط جدا کنید.'
            ], 422);
        }

        $deletedId = $media->id;

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return response()->json([
            'success' => true,
            'deleted_id' => $deletedId,
        ]);
    }

    public function attach(AttachMediaRequest $request): JsonResponse
    {
        $model = $this->resolveModelClass($request->input('mediable_type'));
        $mediable = $model::findOrFail($request->input('mediable_id'));
        $media = Media::findOrFail($request->input('media_id'));

        $mediable->media()->syncWithoutDetaching([
            $media->id => [
                'collection' => $request->input('collection'),
                'sort_order' => $request->input('sort_order', 0),
                'is_featured' => $request->boolean('is_featured'),
                'custom_properties' => $request->input('custom_properties') ? json_decode($request->input('custom_properties'), true) : null,
            ],
        ]);

        return response()->json(['success' => true]);
    }

    public function detach(DetachMediaRequest $request): JsonResponse
    {
        $model = $this->resolveModelClass($request->input('mediable_type'));
        $mediable = $model::findOrFail($request->input('mediable_id'));
        $mediable->media()->detach($request->input('media_id'));

        return response()->json(['success' => true]);
    }

    public function reorder(ReorderMediaRequest $request): JsonResponse
    {
        foreach ($request->input('items', []) as $item) {
            DB::table('mediables')
                ->where('media_id', $item['media_id'])
                ->where('mediable_type', $this->modelClassName($item['mediable_type']))
                ->where('mediable_id', $item['mediable_id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    public function bulkDelete(BulkDeleteMediaRequest $request): JsonResponse
    {
        $mediaItems = Media::whereIn('id', $request->input('ids'))->get();

        foreach ($mediaItems as $media) {
            if ($this->hasAttachments($media)) {
                return response()->json([
                    'message' => 'یکی از رسانه‌ها هنوز در جایی استفاده می‌شود و نمی‌تواند حذف شود.'
                ], 422);
            }
        }

        $deletedIds = [];

        foreach ($mediaItems as $media) {
            $deletedIds[] = $media->id;
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        }

        return response()->json([
            'success' => true,
            'deleted_ids' => $deletedIds,
        ]);
    }

    private function hasAttachments(Media $media): bool
    {
        return DB::table('mediables')->where('media_id', $media->id)->exists()
            || DB::table('products')->where('main_image', $media->path)->exists()
            || DB::table('product_images')->where('image', $media->path)->exists()
            || DB::table('product_variants')->where('image', $media->path)->exists()
            || DB::table('brands')
                ->where('logo', $media->path)
                ->orWhere('featured_image', $media->path)
                ->orWhere('cover_image', $media->path)
                ->exists()
            || DB::table('posts')->where('featured_image', $media->path)->exists()
            || DB::table('pages')->where('featured_image', $media->path)->exists()
            || DB::table('hero_sliders')
                ->where('background_media_id', $media->id)
                ->orWhere('foreground_media_id', $media->id)
                ->exists();
    }

    private function resolveModelClass(string $type): string
    {
        return match ($type) {
            'product' => Product::class,
            'brand' => Brand::class,
            'post' => Post::class,
            'page' => Page::class,
        };
    }

    private function modelClassName(string $type): string
    {
        return match ($type) {
            'product' => Product::class,
            'brand' => Brand::class,
            'post' => Post::class,
            'page' => Page::class,
        };
    }

    private function disk(): string
    {
        return config('shop.uploads.disk', 'public');
    }

    private function storeFile(UploadedFile $file): string
    {
        $path = sprintf('media/%s/%s', now()->format('Y/m'), $this->uniqueFileName($file));

        return $file->storeAs(dirname($path), basename($path), $this->disk());
    }

    private function uniqueFileName(UploadedFile $file): string
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $name = preg_replace('/[^A-Za-z0-9\-_]/', '-', $name) ?: 'media';

        return sprintf('%s-%s.%s', $name, uniqid(), $file->getClientOriginalExtension());
    }
}
