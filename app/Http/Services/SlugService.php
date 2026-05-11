<?php

namespace App\Http\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    public function make(string $value): string
    {
        $slug = trim($value);
        $slug = str_replace(['‌', '_'], [' ', '-'], $slug);
        $slug = preg_replace('/[^\p{L}\p{N}\s-]+/u', '', $slug) ?: '';
        $slug = preg_replace('/[\s-]+/u', '-', trim($slug)) ?: '';
        $slug = trim($slug, '-');

        return $slug !== '' ? mb_strtolower($slug) : Str::random(8);
    }

    public function unique(string $modelClass, string $value, ?int $ignoreId = null, string $column = 'slug'): string
    {
        /** @var class-string<Model> $modelClass */
        $base = $this->make($value);
        $slug = $base;
        $counter = 2;

        while ($modelClass::query()
            ->where($column, $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
