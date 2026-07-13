<?php

namespace Database\Factories;

use App\Http\Services\SlugService;
use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogCategoryFactory extends Factory
{
    protected $model = BlogCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => app(SlugService::class)->unique(BlogCategory::class, $name),
            'description' => fake()->sentence(),
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }
}
