<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_publicly_published_posts_are_rendered(): void
    {
        $published = BlogPost::factory()->published()->create(['title' => 'Visible post']);
        BlogPost::factory()->draft()->create(['title' => 'Draft post']);
        BlogPost::factory()->archived()->create(['title' => 'Archived post']);
        BlogPost::factory()->published()->create(['title' => 'Future post', 'published_at' => now()->addDay()]);

        $response = $this->get('/blog');

        $response->assertOk();
        $posts = collect($response->viewData('page')['props']['posts']['data'] ?? []);

        $this->assertContains($published->id, $posts->pluck('id')->all());
        $this->assertCount(1, $posts);
    }

    public function test_search_category_and_tag_filters_work(): void
    {
        $category = BlogCategory::factory()->create();
        $tag = BlogTag::factory()->create();
        $matching = BlogPost::factory()->published()->create([
            'blog_category_id' => $category->id,
            'title' => 'راهنمای تست فیلتر',
        ]);
        $matching->tags()->sync([$tag->id]);
        BlogPost::factory()->published()->create(['title' => 'Other article']);

        $response = $this->get("/blog?search=فیلتر&category={$category->slug}&tag={$tag->slug}");

        $response->assertOk();
        $posts = collect($response->viewData('page')['props']['posts']['data'] ?? []);

        $this->assertSame([$matching->id], $posts->pluck('id')->all());
    }
}
