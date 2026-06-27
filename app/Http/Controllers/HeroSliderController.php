<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHeroSliderRequest;
use App\Http\Requests\UpdateHeroSliderRequest;
use App\Models\HeroSlider;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HeroSliderController extends Controller
{
    public function index(Request $request): Response
    {
        $rows = (int) $request->integer('rows', 10);

        $sliders = HeroSlider::query()
            ->with(['backgroundMedia', 'foregroundMedia'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder->where('title', 'like', "%{$search}%")
                        ->orWhere('subtitle', 'like', "%{$search}%")
                        ->orWhere('eyebrow_text', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->ordered()
            ->paginate($rows)
            ->withQueryString()
            ->through(fn (HeroSlider $slider) => $this->serializeForAdmin($slider));

        return Inertia::render('HeroSliders/Index', [
            'sliders' => $sliders,
            'filters' => $request->only(['search', 'status', 'rows']),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HeroSliders/Create', $this->formData());
    }

    public function store(StoreHeroSliderRequest $request): RedirectResponse
    {
        HeroSlider::query()->create($request->validated());

        return redirect()->route('admin.hero-sliders.index')->with('success', 'اسلایدر ساخته شد.');
    }

    public function edit(HeroSlider $heroSlider): Response
    {
        $heroSlider->load(['backgroundMedia', 'foregroundMedia']);

        return Inertia::render('HeroSliders/Edit', [
            ...$this->formData(),
            'slider' => $this->serializeForAdmin($heroSlider),
        ]);
    }

    public function update(UpdateHeroSliderRequest $request, HeroSlider $heroSlider): RedirectResponse
    {
        $heroSlider->update($request->validated());

        return redirect()->route('admin.hero-sliders.index')->with('success', 'اسلایدر به‌روزرسانی شد.');
    }

    public function destroy(HeroSlider $heroSlider): RedirectResponse
    {
        $heroSlider->delete();

        return back()->with('success', 'اسلایدر حذف شد.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $rows = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:hero_sliders,id'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($rows): void {
            foreach ($rows['items'] as $item) {
                HeroSlider::query()->whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json(['message' => 'done']);
    }

    public function toggleStatus(HeroSlider $heroSlider): RedirectResponse
    {
        $heroSlider->update(['is_active' => !$heroSlider->is_active]);

        return back()->with('success', 'وضعیت اسلایدر تغییر کرد.');
    }

    private function formData(): array
    {
        return [
            'layouts' => [
                ['label' => 'تصویر چپ، محتوا راست', 'value' => 'image_left_content_right'],
                ['label' => 'محتوا چپ، تصویر راست', 'value' => 'image_right_content_left'],
                ['label' => 'محتوا وسط', 'value' => 'content_center'],
            ],
            'placements' => [
                ['label' => 'اسلایدر اصلی صفحه اول', 'value' => 'hero'],
                ['label' => 'بنر میانی صفحه اول', 'value' => 'middle_banner'],
                ['label' => 'بنر پایین صفحه اول', 'value' => 'footer_banner'],
            ],
        ];
    }

    private function statusOptions(): array
    {
        return [
            ['label' => 'فعال', 'value' => 'active'],
            ['label' => 'غیرفعال', 'value' => 'inactive'],
        ];
    }

    private function serializeForAdmin(HeroSlider $slider): array
    {
        return [
            'id' => $slider->id,
            'title' => $slider->title,
            'subtitle' => $slider->subtitle,
            'eyebrow_text' => $slider->eyebrow_text,
            'description' => $slider->description,
            'background_media_id' => $slider->background_media_id,
            'foreground_media_id' => $slider->foreground_media_id,
            'background_media' => $this->mediaPayload($slider->backgroundMedia),
            'foreground_media' => $this->mediaPayload($slider->foregroundMedia),
            'overlay_opacity' => $slider->overlay_opacity ? (float) $slider->overlay_opacity : 0.55,
            'button_primary_text' => $slider->button_primary_text,
            'button_primary_url' => $slider->button_primary_url,
            'button_secondary_text' => $slider->button_secondary_text,
            'button_secondary_url' => $slider->button_secondary_url,
            'badge_text' => $slider->badge_text,
            'badge_url' => $slider->badge_url,
            'stat_1_label' => $slider->stat_1_label,
            'stat_1_value' => $slider->stat_1_value,
            'stat_2_label' => $slider->stat_2_label,
            'stat_2_value' => $slider->stat_2_value,
            'stat_3_label' => $slider->stat_3_label,
            'stat_3_value' => $slider->stat_3_value,
            'text_color' => $slider->text_color,
            'accent_color' => $slider->accent_color,
            'button_color' => $slider->button_color,
            'layout' => $slider->layout,
            'placement' => $slider->placement ?? 'hero',
            'sort_order' => $slider->sort_order,
            'is_active' => $slider->is_active,
            'starts_at' => optional($slider->starts_at)->format('Y-m-d H:i:s'),
            'ends_at' => optional($slider->ends_at)->format('Y-m-d H:i:s'),
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
