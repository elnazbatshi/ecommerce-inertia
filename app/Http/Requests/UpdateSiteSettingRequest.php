<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'general.site_name' => ['nullable', 'string', 'max:255'],
            'general.site_description' => ['nullable', 'string'],
            'general.logo' => ['nullable'],

            'topbar.items' => ['nullable', 'array'],
            'topbar.items.*.title' => ['required', 'string', 'max:255'],
            'topbar.items.*.description' => ['nullable', 'string', 'max:255'],
            'topbar.items.*.icon' => ['nullable', 'string', 'max:100'],
            'topbar.items.*.is_active' => ['boolean'],
            'topbar.items.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'contact.phone' => ['nullable', 'string', 'max:50'],
            'contact.mobile' => ['nullable', 'string', 'max:50'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.address' => ['nullable', 'string'],
            'contact.working_hours' => ['nullable', 'string', 'max:255'],

            'footer.description' => ['nullable', 'string'],
            'footer.copyright' => ['nullable', 'string', 'max:255'],
            'footer.links' => ['nullable', 'array'],

            'social.instagram' => ['nullable', 'string', 'max:255'],
            'social.telegram' => ['nullable', 'string', 'max:255'],
            'social.whatsapp' => ['nullable', 'string', 'max:255'],
            'social.linkedin' => ['nullable', 'string', 'max:255'],

            'service_features.items' => ['nullable', 'array'],
            'service_features.items.*.title' => ['required', 'string', 'max:255'],
            'service_features.items.*.description' => ['nullable', 'string', 'max:255'],
            'service_features.items.*.icon' => ['nullable', 'string', 'max:100'],
            'service_features.items.*.is_active' => ['boolean'],
            'service_features.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
