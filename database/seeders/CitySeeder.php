<?php

namespace Database\Seeders;

use App\Http\Services\SlugService;
use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $slugService = app(SlugService::class);

        $items = [
            ['province' => 'تهران', 'name' => 'تهران'],
            ['province' => 'تهران', 'name' => 'ری'],
            ['province' => 'تهران', 'name' => 'شمیرانات'],
            ['province' => 'البرز', 'name' => 'کرج'],
            ['province' => 'خراسان رضوی', 'name' => 'مشهد'],
            ['province' => 'اصفهان', 'name' => 'اصفهان'],
            ['province' => 'فارس', 'name' => 'شیراز'],
            ['province' => 'آذربایجان شرقی', 'name' => 'تبریز'],
            ['province' => 'خوزستان', 'name' => 'اهواز'],
            ['province' => 'گیلان', 'name' => 'رشت'],
            ['province' => 'کرمان', 'name' => 'کرمان'],
            ['province' => 'یزد', 'name' => 'یزد'],
            ['province' => 'قم', 'name' => 'قم'],
            ['province' => 'قزوین', 'name' => 'قزوین'],
            ['province' => 'مازندران', 'name' => 'ساری'],
            ['province' => 'آذربایجان غربی', 'name' => 'ارومیه'],
            ['province' => 'هرمزگان', 'name' => 'بندرعباس'],
        ];

        foreach ($items as $index => $item) {
            $province = Province::query()->where('name', $item['province'])->first();
            if (! $province) {
                continue;
            }

            $slug = $slugService->make($item['name']);

            $record = City::query()->withTrashed()->firstOrNew([
                'province_id' => $province->id,
                'slug' => $slug,
            ]);

            $record->fill([
                'name' => $item['name'],
                'code' => null,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
            $record->deleted_at = null;
            $record->save();
        }
    }
}
