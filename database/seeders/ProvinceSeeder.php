<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Http\Services\SlugService;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $slugService = app(SlugService::class);

        $provinces = [
            'آذربایجان شرقی', 'آذربایجان غربی', 'اردبیل', 'اصفهان', 'البرز', 'ایلام', 'بوشهر', 'تهران',
            'چهارمحال و بختیاری', 'خراسان جنوبی', 'خراسان رضوی', 'خراسان شمالی', 'خوزستان', 'زنجان', 'سمنان',
            'سیستان و بلوچستان', 'فارس', 'قزوین', 'قم', 'کردستان', 'کرمان', 'کرمانشاه', 'کهگیلویه و بویراحمد',
            'گلستان', 'گیلان', 'لرستان', 'مازندران', 'مرکزی', 'هرمزگان', 'همدان', 'یزد',
        ];

        foreach ($provinces as $index => $name) {
            $record = Province::query()->withTrashed()->firstOrNew(['name' => $name]);
            $record->fill([
                'slug' => $record->slug ?: $slugService->make($name),
                'code' => null,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
            $record->deleted_at = null;
            $record->save();
        }
    }
}
