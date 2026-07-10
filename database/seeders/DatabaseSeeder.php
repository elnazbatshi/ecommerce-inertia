<?php

namespace Database\Seeders;

use App\Models\User;
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
            SiteSettingSeeder::class,
        ]);

        $admin = User::query()->updateOrCreate([
            'phone' => '09126860148',
        ], [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => '123456789',
        ]);

        $admin->syncRoles(['admin']);
    }
}
