<?php

namespace App\Http\Requests;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSearchSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference_id' => $this->input('type') === 'custom' ? null : $this->input('reference_id'),
            'url' => $this->input('type') === 'custom' ? $this->input('url') : $this->input('url'),
            'sort_order' => $this->input('sort_order') ?? 0,
            'is_active' => $this->boolean('is_active'),
            'starts_at' => $this->filled('starts_at') ? $this->input('starts_at') : null,
            'ends_at' => $this->filled('ends_at') ? $this->input('ends_at') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['product', 'category', 'brand', 'custom'])],
            'reference_id' => ['nullable', 'integer', 'required_unless:type,custom'],
            'url' => ['nullable', 'string', 'max:500', 'required_if:type,custom'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('type') === 'custom' || !$this->filled('reference_id')) {
                    return;
                }

                $exists = match ($this->input('type')) {
                    'product' => Product::query()->whereKey($this->integer('reference_id'))->exists(),
                    'category' => Category::query()->whereKey($this->integer('reference_id'))->exists(),
                    'brand' => Brand::query()->whereKey($this->integer('reference_id'))->exists(),
                    default => false,
                };

                if (!$exists) {
                    $validator->errors()->add('reference_id', 'آیتم انتخاب‌شده معتبر نیست.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'عنوان',
            'type' => 'نوع پیشنهاد',
            'reference_id' => 'آیتم متصل‌شده',
            'url' => 'آدرس',
            'keyword' => 'کلمه کلیدی',
            'icon' => 'آیکون',
            'sort_order' => 'ترتیب',
            'is_active' => 'وضعیت',
            'starts_at' => 'زمان شروع',
            'ends_at' => 'زمان پایان',
        ];
    }
}
