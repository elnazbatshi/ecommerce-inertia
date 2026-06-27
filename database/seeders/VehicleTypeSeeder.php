<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'ماشین', 'slug' => 'car', 'sort_order' => 10],
            ['name' => 'موتورسیکلت', 'slug' => 'motorcycle', 'sort_order' => 20],
            ['name' => 'کامیون', 'slug' => 'truck', 'sort_order' => 30],
            ['name' => 'وانت', 'slug' => 'pickup', 'sort_order' => 40],
        ];

        foreach ($types as $type) {
            VehicleType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
