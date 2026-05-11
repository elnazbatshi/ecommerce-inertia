<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class PublicContentController extends Controller
{
    public function post(Post $post): JsonResponse
    {
        abort_unless($post->status === 'published', 404);
        $post->increment('view_count');

        return response()->json($post->load(['category:id,name,slug', 'tags:id,name,slug', 'products:id,name,slug']));
    }

    public function brand(Brand $brand): JsonResponse
    {
        return response()->json($brand->load(['products:id,name,slug,brand_id,main_image,status']));
    }

    public function page(Page $page): JsonResponse
    {
        abort_unless($page->status === 'published', 404);

        return response()->json($page);
    }
}
