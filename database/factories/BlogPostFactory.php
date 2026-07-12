<?php

namespace Database\Factories;

use App\Http\Services\SlugService;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'user_id' => User::factory(),
            'blog_category_id' => BlogCategory::factory(),
            'title' => $title,
            'slug' => app(SlugService::class)->unique(BlogPost::class, $title),
            'excerpt' => fake()->sentence(12),
            'content' => '<p>'.fake()->paragraphs(4, true).'</p>',
            'featured_image' => null,
            'featured_image_alt' => null,
            'status' => BlogPost::STATUS_DRAFT,
            'is_featured' => false,
            'published_at' => null,
            'views' => 0,
            'meta_title' => null,
            'meta_description' => null,
            'canonical_url' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => BlogPost::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => BlogPost::STATUS_ARCHIVED,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => [
            'is_featured' => true,
        ]);
    }
}
