<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetachMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'media_id' => ['required', 'integer', 'exists:media,id'],
            'mediable_type' => ['required', Rule::in(['product', 'brand', 'post', 'page'])],
            'mediable_id' => ['required', 'integer'],
            'collection' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('mediable_type') && $this->filled('mediable_id')) {
                $class = $this->resolveModelClass($this->input('mediable_type'));
                if (! $class::whereKey($this->input('mediable_id'))->exists()) {
                    $validator->errors()->add('mediable_id', 'مورد انتخابی برای جدا کردن یافت نشد.');
                }
            }
        });
    }

    private function resolveModelClass(string $type): string
    {
        return match ($type) {
            'product' => \App\Models\Product::class,
            'brand' => \App\Models\Brand::class,
            'post' => \App\Models\BlogPost::class,
            'page' => \App\Models\Page::class,
        };
    }
}
