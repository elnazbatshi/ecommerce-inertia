<?php

namespace Database\Seeders;

use App\Http\Services\SlugService;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();
        if (! $user) {
            return;
        }

        $category = BlogCategory::query()->first();
        $tags = BlogTag::query()->limit(3)->pluck('id')->all();

        $posts = [
            ['title' => 'راهنمای انتخاب لنت ترمز مناسب', 'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => now()->subDays(5)],
            ['title' => 'چک لیست نگهداری دوره ای خودرو', 'status' => BlogPost::STATUS_DRAFT, 'published_at' => null],
            ['title' => 'معرفی قطعات مصرفی مهم موتورسیکلت', 'status' => BlogPost::STATUS_ARCHIVED, 'published_at' => now()->subDays(20)],
        ];

        foreach ($posts as $post) {
            $blogPost = BlogPost::query()->updateOrCreate(
                ['slug' => app(SlugService::class)->make($post['title'])],
                [
                    'user_id' => $user->id,
                    'blog_category_id' => $category?->id,
                    'title' => $post['title'],
                    'excerpt' => 'این مطلب نمونه برای راه اندازی مجله موتوپارت ایجاد شده است.',
                    'content' => '<p>در این مقاله نمونه، نکات کاربردی برای خرید، نگهداری و عیب یابی قطعات بررسی می شود.</p>',
                    'status' => $post['status'],
                    'published_at' => $post['published_at'],
                    'is_featured' => $post['status'] === BlogPost::STATUS_PUBLISHED,
                    'views' => 0,
                ]
            );

            $blogPost->tags()->sync($tags);
        }
    }
}
