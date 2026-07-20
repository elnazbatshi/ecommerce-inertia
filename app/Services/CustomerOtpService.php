<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerOtp;
use App\Services\Sms\SmsServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerOtpService
{
    public function __construct(private readonly SmsServiceInterface $sms)
    {
    }

    public function request(string $phone): array
    {
        $this->ensureRateLimit($phone);

        $customerExists = Customer::where('phone', $phone)->exists();
        $code = (string) random_int(100000, 999999);

        Log::info('Customer OTP request started.', [
            'phone' => $this->maskPhone($phone),
            'driver' => config('sms.driver'),
            'send_mode' => config('sms.smsir.send_mode'),
        ]);

        DB::transaction(function () use ($phone, $code): void {
            CustomerOtp::where('phone', $phone)->delete();

            CustomerOtp::create([
                'phone' => $phone,
                'code' => Hash::make($code),
                'expires_at' => now()->addMinutes(2),
            ]);
        });

        Log::info('Customer OTP stored; SMS sender will be called.', [
            'phone' => $this->maskPhone($phone),
            'driver' => config('sms.driver'),
            'send_mode' => config('sms.smsir.send_mode'),
        ]);

        try {
            $this->sms->sendOtp($phone, $code);
        } catch (Throwable $exception) {
            Log::error('OTP SMS sending failed', [
                'phone' => $this->maskPhone($phone),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            throw ValidationException::withMessages([
                'phone' => app()->isLocal()
                    ? 'خطای ارسال پیامک: ' . $exception->getMessage()
                    : 'ارسال کد تایید با خطا مواجه شد. لطفا کمی بعد دوباره تلاش کنید.',
            ]);
        }

        Log::info('Customer OTP SMS sender completed.', [
            'phone' => $this->maskPhone($phone),
            'driver' => config('sms.driver'),
            'send_mode' => config('sms.smsir.send_mode'),
        ]);

        $this->recordSuccessfulRequest($phone);

        return [
            'mode' => $customerExists ? 'login' : 'register',
            'expires_in' => 120,
        ];
    }

    public function verify(string $phone, string $code, ?string $name = null): Customer
    {
        $otp = CustomerOtp::where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'کد تایید منقضی شده است. دوباره کد بگیرید.',
            ]);
        }

        if ($otp->attempts >= 5 || ! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'کد تایید اشتباه است.',
            ]);
        }

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
        CustomerOtp::where('phone', $phone)->delete();

        return $customer;
    }

    private function ensureRateLimit(string $phone): void
    {
        $lastSentAt = Cache::get($this->lastSentKey($phone));

        if ($lastSentAt && now()->diffInSeconds($lastSentAt) < 60) {
            throw ValidationException::withMessages([
                'phone' => 'برای دریافت مجدد کد تایید باید ۶۰ ثانیه صبر کنید.',
            ]);
        }

        if ((int) Cache::get($this->hourlyCountKey($phone), 0) >= 5) {
            throw ValidationException::withMessages([
                'phone' => 'تعداد درخواست کد تایید بیش از حد مجاز است. لطفا بعدا دوباره تلاش کنید.',
            ]);
        }
    }

    private function recordSuccessfulRequest(string $phone): void
    {
        Cache::put($this->lastSentKey($phone), now(), now()->addMinute());

        $key = $this->hourlyCountKey($phone);
        if (! Cache::has($key)) {
            Cache::put($key, 0, now()->addHour());
        }

        Cache::increment($key);
    }

    private function lastSentKey(string $phone): string
    {
        return 'customer-otp:last-sent:'.sha1($phone);
    }

    private function hourlyCountKey(string $phone): string
    {
        return 'customer-otp:hourly-count:'.sha1($phone);
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 7) {
            return '***';
        }

        return substr($phone, 0, 4).'***'.substr($phone, -3);
    }
}
