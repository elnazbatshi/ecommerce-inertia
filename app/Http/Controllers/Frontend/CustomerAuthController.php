<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerOtp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function requestOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $phone = $this->normalizePhone($data['phone']);
        $customerExists = Customer::where('phone', $phone)->exists();
        $code = (string) random_int(100000, 999999);

        CustomerOtp::where('phone', $phone)->delete();

        CustomerOtp::create([
            'phone' => $phone,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(2),
        ]);

        Log::info('Customer OTP requested.', [
            'phone' => $phone,
            'code' => $code,
            'mode' => $customerExists ? 'login' : 'register',
        ]);

        return response()->json([
            'message' => 'کد تایید ارسال شد.',
            'mode' => $customerExists ? 'login' : 'register',
            'expires_in' => 120,
            'debug_code' => app()->isLocal() ? $code : null,
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

        $otp = CustomerOtp::where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'کد تایید منقضی شده است. دوباره کد بگیرید.',
            ]);
        }

        if ($otp->attempts >= 5 || ! Hash::check($data['code'], $otp->code)) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'کد تایید اشتباه است.',
            ]);
        }

        $name = $data['name'] ?? null;

        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => $name ?: "مشتری {$phone}",
                'email' => "{$phone}@customers.motopart.local",
                'status' => 'active',
            ],
        );

        if ((! $customer->name || str_starts_with($customer->name, 'مشتری ')) && $name) {
            $customer->forceFill(['name' => $name])->save();
        }

        $customer->forceFill(['last_login_at' => now()])->save();
        $request->session()->put('customer_id', $customer->id);

        CustomerOtp::where('phone', $phone)->delete();

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
