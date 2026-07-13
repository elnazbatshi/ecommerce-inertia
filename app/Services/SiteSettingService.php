<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettingService
{
    private const PUBLIC_CACHE_KEY = 'site_settings.public';

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = SiteSetting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    public function set(string $group, string $key, mixed $value, ?string $type = null, bool $isPublic = true): SiteSetting
    {
        $setting = SiteSetting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'is_public' => $isPublic,
            ],
        );

        $this->clearPublicCache();

        return $setting;
    }

    public function group(string $group): array
    {
        return SiteSetting::query()
            ->where('group', $group)
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn (SiteSetting $setting) => [$setting->key => $setting->value])
            ->all();
    }

    public function publicSettings(): array
    {
        return Cache::rememberForever(self::PUBLIC_CACHE_KEY, fn () => $this->publicSettingsQuery());
    }

    public function allGrouped(): array
    {
        return SiteSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group')
            ->map(fn ($settings) => $settings->mapWithKeys(fn (SiteSetting $setting) => [$setting->key => $setting->value])->all())
            ->all();
    }

    public function clearPublicCache(): void
    {
        Cache::forget(self::PUBLIC_CACHE_KEY);
    }

    private function publicSettingsQuery(): array
    {
        return SiteSetting::query()
            ->where('is_public', true)
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group')
            ->map(fn ($settings) => $settings->mapWithKeys(fn (SiteSetting $setting) => [$setting->key => $setting->value])->all())
            ->all();
    }
}
