<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPostShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_post_show_page_opens(): void
    {
        $post = BlogPost::factory()->published()->create();

        $this->get(route('blog.show', $post))->assertOk();
    }

    public function test_unpublished_post_returns_404(): void
    {
        $post = BlogPost::factory()->draft()->create();

        $this->get(route('blog.show', $post))->assertNotFound();
    }

    public function test_view_count_increments_once_per_session(): void
    {
        $post = BlogPost::factory()->published()->create(['views' => 0]);

        $this->get(route('blog.show', $post))->assertOk();
        $this->get(route('blog.show', $post))->assertOk();

        $this->assertSame(1, $post->fresh()->views);
    }

    public function test_related_posts_are_found_by_category_or_tag(): void
    {
        $category = BlogCategory::factory()->create();
        $tag = BlogTag::factory()->create();
        $post = BlogPost::factory()->published()->create(['blog_category_id' => $category->id]);
        $post->tags()->sync([$tag->id]);
        $sameCategory = BlogPost::factory()->published()->create(['blog_category_id' => $category->id]);
        $sameTag = BlogPost::factory()->published()->create();
        $sameTag->tags()->sync([$tag->id]);

        $response = $this->get(route('blog.show', $post));

        $related = collect($response->viewData('page')['props']['relatedPosts'] ?? []);
        $this->assertContains($sameCategory->id, $related->pluck('id')->all());
        $this->assertContains($sameTag->id, $related->pluck('id')->all());
    }
}
