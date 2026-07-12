<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBlogPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_post_and_sync_tags_and_products(): void
    {
        $admin = User::factory()->create();
        $category = BlogCategory::factory()->create();
        $tag = BlogTag::factory()->create();
        $product = $this->product('test-product');

        $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
            'title' => 'مقاله تست مدیریت',
            'content' => '<p>متن مقاله</p><script>alert(1)</script>',
            'blog_category_id' => $category->id,
            'status' => 'published',
            'is_featured' => true,
            'tag_ids' => [$tag->id],
            'product_ids' => [$product->id],
        ])->assertRedirect(route('admin.blog-posts.index'));

        $post = BlogPost::query()->where('title', 'مقاله تست مدیریت')->firstOrFail();
        $this->assertSame($admin->id, $post->user_id);
        $this->assertNotNull($post->published_at);
        $this->assertStringNotContainsString('<script>', $post->content);
        $this->assertTrue($post->tags()->whereKey($tag->id)->exists());
        $this->assertTrue($post->products()->whereKey($product->id)->exists());
    }

    public function test_admin_blog_post_pages_render_new_blog_inertia_components(): void
    {
        $admin = User::factory()->create();
        $post = BlogPost::factory()->draft()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.blog-posts.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Blog/Posts/Index', false)
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $post->id)
            );

        $this->actingAs($admin)->get(route('admin.blog-posts.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Blog/Posts/Create', false)
                ->has('categories')
                ->has('tags')
                ->has('products')
                ->has('statuses')
            );

        $this->actingAs($admin)->get(route('admin.blog-posts.edit', $post))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Blog/Posts/Edit', false)
                ->where('post.id', $post->id)
            );
    }

    public function test_admin_blog_taxonomy_pages_render_new_blog_inertia_components(): void
    {
        $admin = User::factory()->create();
        $category = BlogCategory::factory()->create();
        $tag = BlogTag::factory()->create();

        $this->actingAs($admin)->get(route('admin.blog-categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Blog/Categories/Index', false)
                ->has('categories.data', 1)
                ->where('categories.data.0.id', $category->id)
            );

        $this->actingAs($admin)->get(route('admin.blog-tags.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Blog/Tags/Index', false)
                ->has('tags.data', 1)
                ->where('tags.data.0.id', $tag->id)
            );
    }

    public function test_admin_can_update_and_delete_post(): void
    {
        $admin = User::factory()->create();
        $post = BlogPost::factory()->draft()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->put(route('admin.blog-posts.update', $post), [
            'title' => 'Updated title',
            'content' => '<p>Updated content</p>',
            'status' => 'draft',
            'is_featured' => false,
        ])->assertRedirect(route('admin.blog-posts.index'));

        $this->assertSame('Updated title', $post->fresh()->title);

        $this->actingAs($admin)->delete(route('admin.blog-posts.destroy', $post->fresh()))
            ->assertRedirect();

        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }

    public function test_slug_is_unique(): void
    {
        $admin = User::factory()->create();
        BlogPost::factory()->published()->create(['slug' => 'same-slug']);

        $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
            'title' => 'Another post',
            'slug' => 'same-slug',
            'content' => '<p>Content</p>',
            'status' => 'draft',
            'is_featured' => false,
        ])->assertSessionHasErrors('slug');
    }

    public function test_admin_can_toggle_featured(): void
    {
        $admin = User::factory()->create();
        $post = BlogPost::factory()->draft()->create(['is_featured' => false]);

        $this->actingAs($admin)->patch(route('admin.blog-posts.featured', $post), [
            'is_featured' => true,
        ])->assertRedirect();

        $this->assertTrue($post->fresh()->is_featured);
    }

    private function product(string $slug): Product
    {
        return Product::create([
            'name' => $slug,
            'slug' => $slug . '-' . uniqid(),
            'price' => 100000,
            'status' => 'active',
            'type' => 'simple',
            'stock' => 10,
        ]);
    }
}
