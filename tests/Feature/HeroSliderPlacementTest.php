<?php

namespace Tests\Feature;

use App\Models\HeroSlider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HeroSliderPlacementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_frontend_hero_slider_api_filters_by_middle_banner_placement(): void
    {
        HeroSlider::create([
            'title' => 'Hero slider',
            'placement' => 'hero',
            'layout' => 'content_center',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        HeroSlider::create([
            'title' => 'Middle banner',
            'placement' => 'middle_banner',
            'layout' => 'content_center',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->getJson('/api/frontend/hero-sliders?placement=middle_banner')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Middle banner'])
            ->assertJsonMissing(['title' => 'Hero slider']);
    }

    public function test_frontend_hero_slider_api_returns_empty_data_when_no_middle_banner_exists(): void
    {
        HeroSlider::query()->where('placement', 'middle_banner')->delete();

        HeroSlider::create([
            'title' => 'Hero slider',
            'placement' => 'hero',
            'layout' => 'content_center',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->getJson('/api/frontend/hero-sliders?placement=middle_banner')
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
