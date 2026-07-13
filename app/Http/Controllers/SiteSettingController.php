<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSiteSettingRequest;
use App\Services\SiteSettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    public function index(SiteSettingService $settings): Response
    {
        return Inertia::render('SiteSettings/Index', [
            'settings' => $settings->allGrouped(),
        ]);
    }

    public function update(UpdateSiteSettingRequest $request, SiteSettingService $settings): RedirectResponse
    {
        foreach ($request->validated() as $group => $values) {
            foreach ($values as $key => $value) {
                $settings->set($group, $key, $value, $this->typeFor($value), true);
            }
        }

        return back()->with('success', 'تنظیمات سایت به‌روزرسانی شد.');
    }

    private function typeFor(mixed $value): string
    {
        return is_array($value) ? 'array' : 'string';
    }
}
