<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBannerSectionRequest;
use App\Http\Requests\UpdateBannerSectionRequest;
use App\Models\BannerSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BannerSectionController extends Controller
{
    public function index(Request $request): Response
    {
        $rows = (int) $request->integer('rows', 15);

        $sections = BannerSection::query()
            ->withCount('banners')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($builder) => $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%"));
            })
            ->ordered()
            ->paginate($rows)
            ->withQueryString()
            ->through(fn (BannerSection $section) => $this->serializeSection($section));

        return Inertia::render('BannerSections/Index', [
            'sections' => $sections,
            'filters' => $request->only(['search', 'rows']),
            'placements' => $this->placementOptions(),
            'layouts' => $this->layoutOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('BannerSections/Create', $this->formData());
    }

    public function store(StoreBannerSectionRequest $request): RedirectResponse
    {
        BannerSection::create($request->validated());

        return redirect()->route('admin.banner-sections.index')->with('success', 'بخش بنر ساخته شد.');
    }

    public function edit(BannerSection $bannerSection): Response
    {
        return Inertia::render('BannerSections/Edit', [
            ...$this->formData(),
            'section' => $this->serializeSection($bannerSection),
        ]);
    }

    public function update(UpdateBannerSectionRequest $request, BannerSection $bannerSection): RedirectResponse
    {
        $bannerSection->update($request->validated());

        return redirect()->route('admin.banner-sections.index')->with('success', 'بخش بنر به‌روزرسانی شد.');
    }

    public function destroy(BannerSection $bannerSection): RedirectResponse
    {
        $bannerSection->delete();

        return back()->with('success', 'بخش بنر حذف شد.');
    }

    private function formData(): array
    {
        return [
            'placements' => $this->placementOptions(),
            'layouts' => $this->layoutOptions(),
        ];
    }

    private function placementOptions(): array
    {
        return [
            ['label' => 'بالای صفحه اصلی', 'value' => 'home_top'],
            ['label' => 'میانه صفحه اصلی', 'value' => 'home_middle'],
            ['label' => 'پایین صفحه اصلی', 'value' => 'home_bottom'],
        ];
    }

    private function layoutOptions(): array
    {
        return [
            ['label' => 'تمام عرض', 'value' => 'full_width'],
            ['label' => 'دو ستونه', 'value' => 'two_columns'],
            ['label' => 'چهار بنر مساوی', 'value' => 'four_grid'],
            ['label' => 'گرید ترکیبی', 'value' => 'mixed_grid'],
            ['label' => 'اسکرول افقی', 'value' => 'horizontal_scroll'],
        ];
    }

    private function serializeSection(BannerSection $section): array
    {
        return [
            'id' => $section->id,
            'title' => $section->title,
            'key' => $section->key,
            'placement' => $section->placement,
            'layout' => $section->layout,
            'sort_order' => $section->sort_order,
            'is_active' => $section->is_active,
            'banners_count' => $section->banners_count ?? $section->banners()->count(),
        ];
    }
}
