<?php

namespace Database\Seeders;

use App\Http\Services\SlugService;
use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'راهنمای خرید',
            'آموزش و نگهداری',
            'اخبار خودرو',
            'اخبار موتورسیکلت',
            'معرفی قطعات',
            'عیب یابی',
        ];

        foreach ($categories as $index => $name) {
            BlogCategory::query()->updateOrCreate(
                ['slug' => app(SlugService::class)->make($name)],
                [
                    'name' => $name,
                    'description' => "مطالب {$name} در مجله موتوپارت",
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }
}
