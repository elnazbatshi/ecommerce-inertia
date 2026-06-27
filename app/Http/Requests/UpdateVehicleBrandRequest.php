<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $brand = $this->route('vehicleBrand');

        return [
            'name' => ['required', 'string', 'max:255'],
            'vehicle_type_id' => ['required', 'integer', 'exists:vehicle_types,id'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('vehicle_brands', 'slug')->ignore($brand?->id)],
            'type' => ['nullable', Rule::in(['motorcycle', 'car', 'universal', 'truck', 'pickup'])],
            'logo_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'country' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
