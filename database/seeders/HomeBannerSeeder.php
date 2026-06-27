<?php

namespace Database\Seeders;

use App\Models\HeroSlider;
use Illuminate\Database\Seeder;

class HomeBannerSeeder extends Seeder
{
    public function run(): void
    {
        HeroSlider::query()->updateOrCreate(
            [
                'placement' => 'middle_banner',
                'title' => 'قطعات منتخب برای سرویس دوره ای',
            ],
            [
                'eyebrow_text' => 'پیشنهاد MotoPart',
                'subtitle' => 'بنر میانی صفحه اول',
                'description' => 'پکیج روغن، فیلتر، لنت و باتری را برای خودرو و موتورسیکلت از بین برندهای معتبر انتخاب کنید.',
                'button_primary_text' => 'مشاهده محصولات منتخب',
                'button_primary_url' => '#products',
                'button_secondary_text' => null,
                'button_secondary_url' => null,
                'badge_text' => null,
                'badge_url' => null,
                'overlay_opacity' => 0.62,
                'text_color' => '#ffffff',
                'accent_color' => '#D4A017',
                'button_color' => '#D4A017',
                'layout' => 'content_center',
                'sort_order' => 1,
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
            ],
        );
    }
}
