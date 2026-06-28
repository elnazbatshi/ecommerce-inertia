<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Models\Banner;
use App\Models\BannerSection;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(Request $request, BannerSection $bannerSection): Response
    {
        $rows = (int) $request->integer('rows', 15);

        $banners = $bannerSection->banners()
            ->with(['imageMedia', 'mobileImageMedia', 'section'])
            ->ordered()
            ->paginate($rows)
            ->withQueryString()
            ->through(fn (Banner $banner) => $this->serializeBanner($banner));

        return Inertia::render('Banners/Index', [
            'section' => $this->serializeSection($bannerSection),
            'banners' => $banners,
            'filters' => $request->only(['rows']),
        ]);
    }

    public function create(BannerSection $bannerSection): Response
    {
        return Inertia::render('Banners/Create', [
            'section' => $this->serializeSection($bannerSection),
        ]);
    }

    public function store(StoreBannerRequest $request, BannerSection $bannerSection): RedirectResponse
    {
        $bannerSection->banners()->create($request->validated());

        return redirect()->route('admin.banner-sections.banners.index', $bannerSection)->with('success', 'بنر ساخته شد.');
    }

    public function edit(Banner $banner): Response
    {
        $banner->load(['section', 'imageMedia', 'mobileImageMedia']);

        return Inertia::render('Banners/Edit', [
            'section' => $this->serializeSection($banner->section),
            'banner' => $this->serializeBanner($banner),
        ]);
    }

    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $banner->update($request->validated());

        return redirect()->route('admin.banner-sections.banners.index', $banner->banner_section_id)->with('success', 'بنر به‌روزرسانی شد.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $sectionId = $banner->banner_section_id;
        $banner->delete();

        return redirect()->route('admin.banner-sections.banners.index', $sectionId)->with('success', 'بنر حذف شد.');
    }

    private function serializeSection(BannerSection $section): array
    {
        return [
            'id' => $section->id,
            'title' => $section->title,
            'key' => $section->key,
            'placement' => $section->placement,
            'layout' => $section->layout,
        ];
    }

    private function serializeBanner(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'banner_section_id' => $banner->banner_section_id,
            'section_title' => $banner->section?->title,
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'description' => $banner->description,
            'image_media_id' => $banner->image_media_id,
            'mobile_image_media_id' => $banner->mobile_image_media_id,
            'image_media' => $this->mediaPayload($banner->imageMedia),
            'mobile_image_media' => $this->mediaPayload($banner->mobileImageMedia),
            'image' => $banner->imageMedia?->url,
            'mobile_image' => $banner->mobileImageMedia?->url,
            'link_url' => $banner->link_url,
            'button_text' => $banner->button_text,
            'background_color' => $banner->background_color,
            'text_color' => $banner->text_color,
            'sort_order' => $banner->sort_order,
            'is_active' => $banner->is_active,
            'starts_at' => optional($banner->starts_at)->format('Y-m-d H:i:s'),
            'ends_at' => optional($banner->ends_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function mediaPayload(?Media $media): ?array
    {
        if (!$media) {
            return null;
        }

        return [
            'id' => $media->id,
            'url' => $media->url,
            'original_name' => $media->original_name,
            'alt' => $media->alt,
            'title' => $media->title,
            'size' => $media->size,
        ];
    }
}
