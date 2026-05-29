<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'vehicle_brand_id' => ['required', 'integer', 'exists:vehicle_brands,id'],
            'type' => ['required', Rule::in(['motorcycle', 'car'])],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('vehicles', 'slug')->ignore($vehicle?->id)],
            'year_from' => ['nullable', 'integer', 'min:1300', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1300', 'max:2100', 'gte:year_from'],
            'engine' => ['nullable', 'string', 'max:255'],
            'trim' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
