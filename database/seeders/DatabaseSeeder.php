<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            MenuSeeder::class,
            VehicleTypeSeeder::class,
            VehicleBrandSeeder::class,
            ShippingMethodSeeder::class,
            PaymentMethodSeeder::class,
            ProvinceSeeder::class,
            CitySeeder::class,
            HeroSliderSeeder::class,
            HomeBannerSeeder::class,
            HomeBannerSectionSeeder::class,
            AdminUserSeeder::class,
        ]);

        if (! app()->environment('production')) {
            $this->call([
                BlogCategorySeeder::class,
                BlogTagSeeder::class,
                BlogPostSeeder::class,
            ]);
        }
    }
}
