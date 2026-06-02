<?php

namespace App\Http\Middleware;

use App\Services\MenuService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->values(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'header_menu' => fn () => $this->siteMenuItems($request, 'header'),
            'footer_menu' => fn () => $this->siteMenuItems($request, 'footer'),
            'mobile_menu' => fn () => $this->siteMenuItems($request, 'mobile'),
        ];
    }

    private function siteMenuItems(Request $request, string $location): array
    {
        if ($request->is('admin', 'admin/*')) {
            return [];
        }

        return app(MenuService::class)->getByLocation($location)['items'] ?? [];
    }
}
