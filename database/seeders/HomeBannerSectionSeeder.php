<?php

namespace Database\Seeders;

use App\Models\BannerSection;
use Illuminate\Database\Seeder;

class HomeBannerSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'title' => 'بنرهای بالای محصولات',
                'key' => 'home_top_banners',
                'placement' => 'home_top',
                'layout' => 'two_columns',
                'banners' => [
                    ['title' => 'روغن موتورهای منتخب', 'subtitle' => 'MotoPart', 'description' => 'انتخاب دقیق برای سرویس دوره ای', 'link_url' => '#products'],
                    ['title' => 'قطعات مصرفی اصلی', 'subtitle' => 'Original Parts', 'description' => 'لنت، فیلتر، باتری و شمع از برندهای معتبر', 'link_url' => '/products'],
                ],
            ],
            [
                'title' => 'بنرهای میانی',
                'key' => 'home_middle_mixed',
                'placement' => 'home_middle',
                'layout' => 'mixed_grid',
                'banners' => [
                    ['title' => 'سرویس کامل خودرو', 'subtitle' => 'Car Care', 'description' => 'قطعات سازگار با مدل خودروی شما'],
                    ['title' => 'موتورسیکلت', 'subtitle' => 'Motorcycle', 'description' => 'روغن و قطعات مخصوص مسیرهای روزانه'],
                    ['title' => 'ارسال سریع', 'subtitle' => 'Fast Delivery', 'description' => 'تحویل سریع سفارش‌های ضروری'],
                    ['title' => 'ضمانت اصالت', 'subtitle' => 'Original', 'description' => 'خرید با اطمینان از اصالت کالا'],
                ],
            ],
            [
                'title' => 'بنرهای پایین صفحه',
                'key' => 'home_bottom_banners',
                'placement' => 'home_bottom',
                'layout' => 'full_width',
                'banners' => [
                    ['title' => 'برای انتخاب قطعه مطمئن‌تر شروع کنید', 'subtitle' => 'MotoPart', 'description' => 'با دسته‌بندی، برند و مدل وسیله نقلیه، قطعه مناسب را سریع‌تر پیدا کنید.', 'link_url' => '/products'],
                ],
            ],
        ];

        foreach ($sections as $index => $definition) {
            $section = BannerSection::query()->updateOrCreate(
                ['key' => $definition['key']],
                [
                    'title' => $definition['title'],
                    'placement' => $definition['placement'],
                    'layout' => $definition['layout'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );

            foreach ($definition['banners'] as $bannerIndex => $banner) {
                $section->banners()->updateOrCreate(
                    ['title' => $banner['title']],
                    [
                        'subtitle' => $banner['subtitle'] ?? null,
                        'description' => $banner['description'] ?? null,
                        'link_url' => $banner['link_url'] ?? null,
                        'button_text' => 'مشاهده',
                        'background_color' => '#111111',
                        'text_color' => '#ffffff',
                        'sort_order' => $bannerIndex + 1,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
