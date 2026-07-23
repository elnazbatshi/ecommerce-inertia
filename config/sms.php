<?php

return [
    'driver' => env('SMS_DRIVER', 'smsir'),
    'fake' => env('SMS_FAKE', env('APP_ENV') !== 'production'),

    'smsir' => [
        'api_key' => env('SMSIR_API_KEY'),
        'line_number' => env('SMSIR_LINE_NUMBER'),
        'send_mode' => env('SMSIR_SEND_MODE', 'text'),
        'template_otp' => env('SMSIR_TEMPLATE_OTP'),
        'base_url' => env('SMSIR_BASE_URL', 'https://api.sms.ir/v1'),
        'verify_ssl' => filter_var(
            env('SMSIR_VERIFY_SSL', env('APP_ENV') === 'production'),
            FILTER_VALIDATE_BOOL,
        ),
    ],
];
