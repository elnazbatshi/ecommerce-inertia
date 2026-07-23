<?php

namespace App\Services\Sms;

interface SmsServiceInterface
{
    public function sendOtp(string $mobile, string $code): void;
}
