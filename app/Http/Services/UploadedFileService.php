<?php

namespace App\Http\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadedFileService
{
    public function store(UploadedFile $file, string $path): string
    {
        return $file->store($path, $this->disk());
    }

    public function replace(?string $currentPath, UploadedFile $file, string $path): string
    {
        $this->delete($currentPath);

        return $this->store($file, $path);
    }

    public function delete(?string $path): void
    {
        if ($path) {
            if (Media::query()->where('path', $path)->exists()) {
                return;
            }

            Storage::disk($this->disk())->delete($path);
        }
    }

    private function disk(): string
    {
        return (string) config('shop.uploads.disk', 'public');
    }
}
