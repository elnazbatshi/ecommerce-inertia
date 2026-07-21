<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SiteSettingService::class);

        foreach ($this->defaults() as $group => $values) {
            foreach ($values as $key => $value) {
                $settings->set($group, $key, $value, is_array($value) ? 'array' : 'string', true);
            }
        }

        $contactHero = SiteSetting::query()->firstOrCreate(
            [
                'group' => 'page_heroes',
                'key' => 'contact',
            ],
            [
                'value' => [
                    'image_url' => null,
                    'image_media' => null,
                    'title' => 'تماس با ما',
                    'subtitle' => 'ما اینجاییم تا به شما کمک کنیم',
                    'description' => 'برای مشاوره قبل از خرید، پیگیری سفارش، پشتیبانی پس از خرید یا همکاری با موتوشهر با ما در ارتباط باشید.',
                    'badge' => 'ارتباط با پشتیبانی موتوشهر',
                    'overlay_opacity' => 70,
                    'text_position' => 'right',
                    'is_active' => true,
                ],
                'type' => 'array',
                'is_public' => true,
            ],
        );

        if ($contactHero->wasRecentlyCreated) {
            $settings->clearPublicCache();
        }
    }

    private function defaults(): array
    {
        return [
            'general' => [
                'site_name' => 'MotoPart',
                'site_description' => 'فروشگاه تخصصی قطعات موتورسیکلت',
                'logo' => null,
            ],
            'topbar' => [
                'items' => [
                    ['title' => 'ارسال سریع به سراسر کشور', 'description' => null, 'icon' => 'truck', 'is_active' => true, 'sort_order' => 1],
                    ['title' => 'ضمانت اصالت کالا', 'description' => null, 'icon' => 'shield', 'is_active' => true, 'sort_order' => 2],
                    ['title' => 'پشتیبانی تخصصی', 'description' => null, 'icon' => 'headset', 'is_active' => true, 'sort_order' => 3],
                ],
            ],
            'contact' => [
                'phone' => null,
                'mobile' => null,
                'email' => null,
                'address' => null,
                'working_hours' => null,
            ],
            'footer' => [
                'description' => null,
                'copyright' => null,
                'links' => [],
            ],
            'social' => [
                'instagram' => null,
                'telegram' => null,
                'whatsapp' => null,
                'linkedin' => null,
            ],
            'service_features' => [
                'items' => [
                    ['title' => 'ارسال سریع', 'description' => 'تحویل سریع سفارش‌ها در سراسر کشور', 'icon' => 'truck', 'is_active' => true, 'sort_order' => 1],
                    ['title' => 'ضمانت اصالت کالا', 'description' => 'تمام محصولات با تضمین اصالت عرضه می‌شوند', 'icon' => 'shield', 'is_active' => true, 'sort_order' => 2],
                    ['title' => 'پشتیبانی تخصصی', 'description' => 'راهنمایی تخصصی برای انتخاب قطعه مناسب', 'icon' => 'headset', 'is_active' => true, 'sort_order' => 3],
                    ['title' => '۷ روز ضمانت بازگشت', 'description' => 'امکان بازگشت کالا طبق شرایط فروشگاه', 'icon' => 'rotate', 'is_active' => true, 'sort_order' => 4],
                ],
            ],
        ];
    }
}
