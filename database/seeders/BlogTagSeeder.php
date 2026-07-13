<?php

namespace Database\Seeders;

use App\Http\Services\SlugService;
use App\Models\BlogTag;
use Illuminate\Database\Seeder;

class BlogTagSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['لنت ترمز', 'روغن موتور', 'فیلتر هوا', 'باطری', 'جلوبندی', 'موتور'] as $name) {
            BlogTag::query()->updateOrCreate(
                ['slug' => app(SlugService::class)->make($name)],
                ['name' => $name]
            );
        }
    }
}
