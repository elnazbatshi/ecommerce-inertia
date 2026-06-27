<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoryIconSeeder extends Seeder
{
    public function run(): void
    {
        $icons = [
            'oil-lubricants' => 'oil',
            '10w40' => 'oil',
            '20w50' => 'oil',
            'scooter-oil' => 'oil',
            'oil-filter' => 'filter',
            'main-parts' => 'engine',
            'brake-pad' => 'brake',
            'battery' => 'battery',
            'spark-plug' => 'spark',
            'chain' => 'chain',
            'air-filter' => 'filter',
            'accessories-parts' => 'parts',
            'mirror' => 'mirror',
            'turn-signal' => 'signal',
            'body-panel' => 'body',
            'light' => 'light',
            'handle-grip' => 'grip',
            'equipment-tools' => 'tools',
            'helmet' => 'helmet',
            'gloves' => 'gloves',
            'repair-tools' => 'tools',
            'motorcycle-lock' => 'lock',
            'motorcycle-cover' => 'cover',
        ];

        foreach ($icons as $slug => $icon) {
            Category::query()
                ->where('slug', $slug)
                ->update(['icon' => $icon]);
        }
    }
}
