<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BannerSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerSectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sections = BannerSection::query()
            ->active()
            ->forPlacement($request->query('placement'))
            ->with(['banners' => fn ($query) => $query
                ->with(['imageMedia', 'mobileImageMedia'])
                ->currentlyVisible()
                ->ordered()])
            ->ordered()
            ->get()
            ->filter(fn (BannerSection $section) => $section->banners->isNotEmpty())
            ->map(fn (BannerSection $section) => [
                'id' => $section->id,
                'title' => $section->title,
                'key' => $section->key,
                'placement' => $section->placement,
                'layout' => $section->layout,
                'sort_order' => $section->sort_order,
                'banners' => $section->banners
                    ->map(fn (Banner $banner) => [
                        'id' => $banner->id,
                        'title' => $banner->title,
                        'subtitle' => $banner->subtitle,
                        'description' => $banner->description,
                        'image' => $banner->imageMedia?->url,
                        'mobile_image' => $banner->mobileImageMedia?->url,
                        'link_url' => $banner->link_url,
                        'button_text' => $banner->button_text,
                        'background_color' => $banner->background_color,
                        'text_color' => $banner->text_color,
                        'sort_order' => $banner->sort_order,
                    ])
                    ->values(),
            ])
            ->values();

        return response()->json(['data' => $sections]);
    }
}
