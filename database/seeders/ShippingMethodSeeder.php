<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'پست پیشتاز',
                'slug' => 'post-pishtaz',
                'description' => 'ارسال با پست پیشتاز',
                'type' => 'fixed',
                'base_cost' => 75000,
                'estimated_delivery_days' => '2 تا 4 روز کاری',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'تیپاکس',
                'slug' => 'tipax',
                'description' => 'ارسال با تیپاکس',
                'type' => 'city_based',
                'base_cost' => 90000,
                'estimated_delivery_days' => '1 تا 3 روز کاری',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'پیک موتوری تهران',
                'slug' => 'tehran-courier',
                'description' => 'ویژه شهر تهران',
                'type' => 'city_based',
                'base_cost' => 120000,
                'estimated_delivery_days' => 'همان روز',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'تحویل حضوری',
                'slug' => 'pickup',
                'description' => 'تحویل از فروشگاه',
                'type' => 'pickup',
                'base_cost' => 0,
                'estimated_delivery_days' => 'تحویل فوری',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'ارسال رایگان',
                'slug' => 'free-shipping',
                'description' => 'برای سفارش‌های واجد شرایط',
                'type' => 'free',
                'base_cost' => 0,
                'free_from_amount' => 5000000,
                'estimated_delivery_days' => '2 تا 5 روز کاری',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::query()->updateOrCreate(
                ['slug' => $method['slug']],
                $method
            );
        }
    }
}

