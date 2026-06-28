<?php

namespace Database\Seeders;

use App\Models\HeroSlider;
use App\Models\Media;
use Illuminate\Database\Seeder;

class HeroSliderSeeder extends Seeder
{
    public function run(): void
    {
        $background = Media::query()->updateOrCreate(
            ['disk' => 'public', 'path' => 'hero/motopart-hero-background.svg'],
            [
                'filename' => 'motopart-hero-background.svg',
                'original_name' => 'motopart-hero-background.svg',
                'mime_type' => 'image/svg+xml',
                'extension' => 'svg',
                'size' => 0,
                'width' => 1600,
                'height' => 900,
                'alt' => 'پس زمینه صنعتی MotoPart',
                'title' => 'پس زمینه اسلایدر اصلی MotoPart',
            ]
        );

        $foreground = Media::query()->updateOrCreate(
            ['disk' => 'public', 'path' => 'hero/motopart-hero-foreground.svg'],
            [
                'filename' => 'motopart-hero-foreground.svg',
                'original_name' => 'motopart-hero-foreground.svg',
                'mime_type' => 'image/svg+xml',
                'extension' => 'svg',
                'size' => 0,
                'width' => 900,
                'height' => 620,
                'alt' => 'قطعات پریمیوم موتورسیکلت MotoPart',
                'title' => 'تصویر اسلایدر اصلی MotoPart',
            ]
        );

        HeroSlider::query()->updateOrCreate(
            [
                'title' => 'قطعات اصلی برای موتورهای حرفه‌ای',
                'placement' => 'hero',
            ],
            [
                'subtitle' => 'فروشگاه تخصصی قطعات موتورسیکلت',
                'eyebrow_text' => 'MotoPart',
                'description' => 'فروشگاه تخصصی روغن موتور و قطعات پریمیوم موتورسیکلت با تضمین اصالت، سازگاری دقیق و تحویل سریع.',
                'background_media_id' => $background->id,
                'foreground_media_id' => $foreground->id,
                'overlay_opacity' => 0.55,
                'button_primary_text' => 'مشاهده محصولات ویژه',
                'button_primary_url' => '#products',
                'button_secondary_text' => 'جستجوی قطعه سازگار',
                'button_secondary_url' => '#finder',
                'badge_text' => 'Industrial Selection',
                'badge_url' => null,
                'stat_1_label' => 'قطعه موجود',
                'stat_1_value' => '+1200',
                'stat_2_label' => 'رضایت مشتریان',
                'stat_2_value' => '98%',
                'stat_3_label' => 'ارسال سریع',
                'stat_3_value' => '24h',
                'text_color' => '#ffffff',
                'accent_color' => '#D4A017',
                'button_color' => '#D4A017',
                'layout' => 'image_left_content_right',
                'sort_order' => 0,
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
            ]
        );
    }
}
