<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'پرداخت آنلاین زرین‌پال',
                'slug' => 'zarinpal-online',
                'description' => 'پرداخت اینترنتی از درگاه زرین‌پال',
                'driver' => 'zarinpal',
                'fee_type' => 'none',
                'fee_value' => 0,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'کارت به کارت',
                'slug' => 'card-to-card',
                'description' => 'پرداخت دستی و ثبت فیش',
                'driver' => 'card_to_card',
                'fee_type' => 'none',
                'fee_value' => 0,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'پرداخت در محل',
                'slug' => 'cash-on-delivery',
                'description' => 'پرداخت هنگام تحویل سفارش',
                'driver' => 'cash_on_delivery',
                'fee_type' => 'fixed',
                'fee_value' => 20000,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'کیف پول',
                'slug' => 'wallet',
                'description' => 'پرداخت از موجودی کیف پول',
                'driver' => 'wallet',
                'fee_type' => 'none',
                'fee_value' => 0,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'پرداخت دستی',
                'slug' => 'manual-payment',
                'description' => 'پرداخت و تایید توسط ادمین',
                'driver' => 'manual',
                'fee_type' => 'none',
                'fee_value' => 0,
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['slug' => $method['slug']],
                $method
            );
        }
    }
}

