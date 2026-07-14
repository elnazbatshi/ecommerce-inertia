<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroSlider;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeroSliderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $placement = $request->query('placement', 'hero');

        $sliders = HeroSlider::query()
            ->with(['backgroundMedia', 'foregroundMedia'])
            ->currentlyVisible()
            ->where('placement', $placement)
            ->ordered()
            ->get()
            ->map(fn (HeroSlider $slider) => [
                'id' => $slider->id,
                'placement' => $slider->placement,
                'eyebrow_text' => $slider->eyebrow_text,
                'title' => $slider->title,
                'subtitle' => $slider->subtitle,
                'description' => $slider->description,
                'background_image' => $slider->backgroundMedia?->url,
                'background_alt' => $this->mediaAlt($slider->backgroundMedia, $slider->title),
                'foreground_image' => $slider->foregroundMedia?->url,
                'foreground_alt' => $this->mediaAlt($slider->foregroundMedia, $slider->title),
                'overlay_opacity' => $slider->overlay_opacity ? (float) $slider->overlay_opacity : 0.55,
                'show_overlay_content' => $slider->show_overlay_content ?? true,
                'badge' => [
                    'text' => $slider->badge_text,
                    'url' => $slider->badge_url,
                ],
                'buttons' => [
                    'primary' => [
                        'text' => $slider->button_primary_text,
                        'url' => $slider->button_primary_url,
                    ],
                    'secondary' => [
                        'text' => $slider->button_secondary_text,
                        'url' => $slider->button_secondary_url,
                    ],
                ],
                'stats' => collect([
                    ['label' => $slider->stat_1_label, 'value' => $slider->stat_1_value],
                    ['label' => $slider->stat_2_label, 'value' => $slider->stat_2_value],
                    ['label' => $slider->stat_3_label, 'value' => $slider->stat_3_value],
                ])->filter(fn (array $stat) => $stat['label'] || $stat['value'])->values(),
                'colors' => [
                    'text' => $slider->text_color,
                    'accent' => $slider->accent_color,
                    'button' => $slider->button_color,
                ],
                'layout' => $slider->layout,
            ]);

        return response()->json(['data' => $sliders]);
    }

    private function mediaAlt(?Media $media, string $fallback): string
    {
        return $media?->alt ?: $media?->title ?: $fallback;
    }
}
