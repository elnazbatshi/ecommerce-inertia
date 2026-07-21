<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\CustomerOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    public function __construct(private readonly CustomerOtpService $otps)
    {
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $phone = $this->normalizePhone($data['phone']);
        $otp = $this->otps->request($phone);

        return response()->json([
            'message' => 'کد تایید ارسال شد.',
            'mode' => $otp['mode'],
            'expires_in' => $otp['expires_in'],
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'code' => ['required', 'digits:6'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $phone = $this->normalizePhone($data['phone']);

        $customer = $this->otps->verify(
            $phone,
            $data['code'],
            $data['name'] ?? null,
        );

        $request->session()->put('customer_id', $customer->id);

        return response()->json([
            'message' => 'ورود با موفقیت انجام شد.',
            'user' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('customer_id');

        return back();
    }

    private function normalizePhone(string $phone): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return preg_replace('/[^\d+]/', '', str_replace($persian, $english, trim($phone)));
    }
}
