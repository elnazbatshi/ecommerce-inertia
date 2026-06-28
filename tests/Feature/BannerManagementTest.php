<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\BannerSection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_banner_section(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/banner-sections', [
                'title' => 'Test section',
                'key' => 'test_section',
                'placement' => 'home_middle',
                'layout' => 'mixed_grid',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.banner-sections.index'));

        $this->assertDatabaseHas('banner_sections', [
            'key' => 'test_section',
            'placement' => 'home_middle',
        ]);
    }

    public function test_admin_can_create_banner(): void
    {
        $admin = User::factory()->create();
        $section = $this->section();

        $this->actingAs($admin)
            ->post("/admin/banner-sections/{$section->id}/banners", [
                'title' => 'Banner title',
                'subtitle' => 'Subtitle',
                'description' => 'Description',
                'link_url' => '/products',
                'button_text' => 'View',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.banner-sections.banners.index', $section));

        $this->assertDatabaseHas('banners', [
            'banner_section_id' => $section->id,
            'title' => 'Banner title',
        ]);
    }

    public function test_api_returns_only_active_sections_and_current_banners(): void
    {
        $active = $this->section('active_section', true, 'home_middle', 'mixed_grid');
        $inactive = $this->section('inactive_section', false, 'home_middle');

        $this->banner($active, 'Visible banner');
        $this->banner($active, 'Inactive banner', false);
        $this->banner($active, 'Expired banner', true, now()->subDays(3), now()->subDay());
        $this->banner($inactive, 'Hidden section banner');

        $this->getJson('/api/frontend/banner-sections?placement=home_middle')
            ->assertOk()
            ->assertJsonFragment(['key' => 'active_section'])
            ->assertJsonFragment(['title' => 'Visible banner'])
            ->assertJsonMissing(['key' => 'inactive_section'])
            ->assertJsonMissing(['title' => 'Inactive banner'])
            ->assertJsonMissing(['title' => 'Expired banner']);
    }

    public function test_api_filters_by_placement_and_returns_layout(): void
    {
        $middle = $this->section('middle_section', true, 'home_middle', 'mixed_grid');
        $bottom = $this->section('bottom_section', true, 'home_bottom', 'full_width');
        $this->banner($middle, 'Middle banner');
        $this->banner($bottom, 'Bottom banner');

        $this->getJson('/api/frontend/banner-sections?placement=home_middle')
            ->assertOk()
            ->assertJsonFragment(['key' => 'middle_section'])
            ->assertJsonFragment(['layout' => 'mixed_grid'])
            ->assertJsonMissing(['key' => 'bottom_section']);
    }

    public function test_soft_deleted_records_do_not_appear_in_api(): void
    {
        $section = $this->section('deleted_section', true, 'home_middle');
        $banner = $this->banner($section, 'Deleted banner');

        $banner->delete();

        $this->getJson('/api/frontend/banner-sections?placement=home_middle')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Deleted banner']);

        $section->delete();

        $this->getJson('/api/frontend/banner-sections?placement=home_middle')
            ->assertOk()
            ->assertJsonMissing(['key' => 'deleted_section']);
    }

    private function section(string $key = 'test_section', bool $active = true, string $placement = 'home_middle', string $layout = 'two_columns'): BannerSection
    {
        return BannerSection::create([
            'title' => $key,
            'key' => $key,
            'placement' => $placement,
            'layout' => $layout,
            'sort_order' => 1,
            'is_active' => $active,
        ]);
    }

    private function banner(BannerSection $section, string $title, bool $active = true, mixed $startsAt = null, mixed $endsAt = null): Banner
    {
        return Banner::create([
            'banner_section_id' => $section->id,
            'title' => $title,
            'subtitle' => 'Subtitle',
            'description' => 'Description',
            'sort_order' => 1,
            'is_active' => $active,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
