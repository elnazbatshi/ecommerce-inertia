<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingService;
use Illuminate\Http\JsonResponse;

class SiteSettingController extends Controller
{
    public function index(SiteSettingService $settings): JsonResponse
    {
        return response()->json([
            'data' => $settings->publicSettings(),
        ]);
    }
}
