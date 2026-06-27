<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHeroSliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'background_media_id' => $this->filled('background_media_id') ? $this->integer('background_media_id') : null,
            'foreground_media_id' => $this->filled('foreground_media_id') ? $this->integer('foreground_media_id') : null,
            'overlay_opacity' => $this->input('overlay_opacity') ?? 0.55,
            'placement' => $this->input('placement') ?: 'hero',
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
            'subtitle' => ['nullable', 'string', 'max:255'],
            'eyebrow_text' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'background_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'foreground_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'overlay_opacity' => ['nullable', 'numeric', 'between:0,1'],
            'button_primary_text' => ['nullable', 'string', 'max:255'],
            'button_primary_url' => ['nullable', 'string', 'max:500'],
            'button_secondary_text' => ['nullable', 'string', 'max:255'],
            'button_secondary_url' => ['nullable', 'string', 'max:500'],
            'badge_text' => ['nullable', 'string', 'max:255'],
            'badge_url' => ['nullable', 'string', 'max:500'],
            'stat_1_label' => ['nullable', 'string', 'max:255'],
            'stat_1_value' => ['nullable', 'string', 'max:255'],
            'stat_2_label' => ['nullable', 'string', 'max:255'],
            'stat_2_value' => ['nullable', 'string', 'max:255'],
            'stat_3_label' => ['nullable', 'string', 'max:255'],
            'stat_3_value' => ['nullable', 'string', 'max:255'],
            'text_color' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'button_color' => ['nullable', 'string', 'max:20'],
            'layout' => ['required', Rule::in(['image_left_content_right', 'image_right_content_left', 'content_center'])],
            'placement' => ['required', Rule::in(['hero', 'middle_banner', 'footer_banner'])],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
