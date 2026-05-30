<?php

namespace Database\Seeders;

use App\Models\VehicleBrand;
use Illuminate\Database\Seeder;

class VehicleBrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Honda', 'slug' => 'honda', 'type' => 'motorcycle', 'country' => 'Japan'],
            ['name' => 'Yamaha', 'slug' => 'yamaha', 'type' => 'motorcycle', 'country' => 'Japan'],
            ['name' => 'Suzuki', 'slug' => 'suzuki', 'type' => 'motorcycle', 'country' => 'Japan'],
            ['name' => 'Kawasaki', 'slug' => 'kawasaki', 'type' => 'motorcycle', 'country' => 'Japan'],
            ['name' => 'KTM', 'slug' => 'ktm', 'type' => 'motorcycle', 'country' => 'Austria'],
            ['name' => 'Benelli', 'slug' => 'benelli', 'type' => 'motorcycle', 'country' => 'Italy'],
            ['name' => 'Bajaj', 'slug' => 'bajaj', 'type' => 'motorcycle', 'country' => 'India'],
            ['name' => 'TVS', 'slug' => 'tvs', 'type' => 'motorcycle', 'country' => 'India'],
            ['name' => 'Peugeot', 'slug' => 'peugeot', 'type' => 'car', 'country' => 'France'],
            ['name' => 'Pride', 'slug' => 'pride', 'type' => 'car', 'country' => 'South Korea'],
            ['name' => 'Saipa', 'slug' => 'saipa', 'type' => 'car', 'country' => 'Iran'],
            ['name' => 'Iran Khodro', 'slug' => 'iran-khodro', 'type' => 'car', 'country' => 'Iran'],
            ['name' => 'Hyundai', 'slug' => 'hyundai', 'type' => 'car', 'country' => 'South Korea'],
            ['name' => 'Kia', 'slug' => 'kia', 'type' => 'car', 'country' => 'South Korea'],
            ['name' => 'Toyota', 'slug' => 'toyota', 'type' => 'car', 'country' => 'Japan'],
            ['name' => 'Nissan', 'slug' => 'nissan', 'type' => 'car', 'country' => 'Japan'],
        ];

        foreach ($brands as $index => $brand) {
            $record = VehicleBrand::query()
                ->withTrashed()
                ->firstOrNew(['slug' => $brand['slug']]);

            $record->fill([
                'name' => $brand['name'],
                'type' => $brand['type'],
                'country' => $brand['country'],
                'logo_media_id' => null,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            $record->deleted_at = null;
            $record->save();
        }
    }
}
