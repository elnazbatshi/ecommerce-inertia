<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SmsIrService implements SmsServiceInterface
{
    private const OTP_TEMPLATE_PARAMETER = 'CODE';

    public function sendOtp(string $mobile, string $code): void
    {
        if ($this->fakeEnabled()) {
            Log::info('Fake customer OTP SMS.', [
                'mobile' => $mobile,
                'driver' => 'smsir',
            ]);

            return;
        }

        match ($this->sendMode()) {
            'text' => $this->sendTextOtp($mobile, $code),
            'verify' => $this->sendVerifyOtp($mobile, $code),
        };
    }

    private function sendTextOtp(string $mobile, string $code): void
    {
        $apiKey = config('sms.smsir.api_key');
        $lineNumber = config('sms.smsir.line_number');

        if (blank($apiKey)) {
            throw new RuntimeException('SMS.ir API key is not configured.');
        }

        if (blank($lineNumber)) {
            throw new RuntimeException('SMS.ir line number is required for text send mode.');
        }

        $this->postToSmsIr(
            $this->textEndpoint(),
            $this->textPayload($mobile, $code, (string) $lineNumber),
            $mobile,
            $apiKey,
            'text',
        );
    }

    private function sendVerifyOtp(string $mobile, string $code): void
    {
        $apiKey = config('sms.smsir.api_key');
        $templateId = config('sms.smsir.template_otp');

        if (blank($apiKey) || blank($templateId)) {
            throw new RuntimeException('SMS.ir API key or OTP template id is not configured.');
        }

        $this->postToSmsIr(
            $this->verifyEndpoint(),
            $this->verifyPayload($mobile, $code, (int) $templateId),
            $mobile,
            $apiKey,
            'verify',
        );
    }

    private function postToSmsIr(string $endpoint, array $payload, string $mobile, string $apiKey, string $mode): void
    {
        $requestStartedAt = microtime(true);
        $httpStartedAt = null;

        try {
            Log::info('SMS.ir OTP request started.', [
                'mobile' => $this->maskMobile($mobile),
                'mode' => $mode,
                'endpoint' => $endpoint,
                'template_id' => $payload['templateId'] ?? null,
                'template_id_type' => isset($payload['templateId']) ? gettype($payload['templateId']) : null,
                'parameter_names' => collect($payload['parameters'] ?? [])->pluck('name')->values()->all(),
            ]);

            $httpStartedAt = microtime(true);

            $http = Http::timeout(15);

            if (! config('sms.smsir.verify_ssl', true)) {
                $http = $http->withoutVerifying();
            }

            $response = $http->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-API-KEY' => $apiKey,
            ])
                ->post($endpoint, $payload);
            $httpFinishedAt = microtime(true);

            if ($response->failed() || ! $this->smsIrResponseWasSuccessful($response->json())) {
                Log::warning('SMS.ir OTP request rejected.', [
                    'mobile' => $this->maskMobile($mobile),
                    'mode' => $mode,
                    'http_status' => $response->status(),
                    'response_status' => data_get($response->json(), 'status'),
                    'response_message' => data_get($response->json(), 'message'),
                    'http_ms' => $this->durationMs($httpStartedAt, $httpFinishedAt),
                    'total_elapsed_ms' => $this->durationMs($requestStartedAt, $httpFinishedAt),
                ]);

                throw new RuntimeException("SMS.ir {$mode} OTP request failed: ".$response->body());
            }

            Log::info('SMS.ir OTP request completed.', [
                'mobile' => $this->maskMobile($mobile),
                'mode' => $mode,
                'http_status' => $response->status(),
                'response_status' => data_get($response->json(), 'status'),
                'response_message' => data_get($response->json(), 'message'),
                'http_ms' => $this->durationMs($httpStartedAt, $httpFinishedAt),
                'total_elapsed_ms' => $this->durationMs($requestStartedAt, $httpFinishedAt),
            ]);
        } catch (Throwable $exception) {
            $failedAt = microtime(true);

            Log::error('SMS.ir OTP send failed.', [
                'mobile' => $this->maskMobile($mobile),
                'mode' => $mode,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'http_ms' => $httpStartedAt ? $this->durationMs($httpStartedAt, $failedAt) : null,
                'total_elapsed_ms' => $this->durationMs($requestStartedAt, $failedAt),
            ]);

            throw $exception;
        }
    }

    private function fakeEnabled(): bool
    {
        return (bool) config('sms.fake');
    }

    private function sendMode(): string
    {
        $mode = strtolower((string) config('sms.smsir.send_mode', 'text'));

        if (! in_array($mode, ['text', 'verify'], true)) {
            throw new RuntimeException('SMS.ir send mode must be either text or verify.');
        }

        return $mode;
    }

    private function textEndpoint(): string
    {
        return rtrim((string) config('sms.smsir.base_url'), '/').'/send/bulk';
    }

    private function verifyEndpoint(): string
    {
        return rtrim((string) config('sms.smsir.base_url'), '/').'/send/verify';
    }

    private function textPayload(string $mobile, string $code, string $lineNumber): array
    {
        return [
            'lineNumber' => $lineNumber,
            'messageText' => "کد ورود به موتوشهر: {$code}\nاین کد را در اختیار دیگران قرار ندهید.",
            'mobiles' => [$mobile],
        ];
    }

    private function verifyPayload(string $mobile, string $code, int $templateId): array
    {
        return [
            'mobile' => $mobile,
            'templateId' => $templateId,
            'parameters' => [
                [
                    'name' => self::OTP_TEMPLATE_PARAMETER,
                    'value' => $code,
                ],
            ],
        ];
    }

    private function smsIrResponseWasSuccessful(?array $response): bool
    {
        $status = data_get($response, 'status');

        return $status === true || $status === 1;
    }

    private function maskMobile(string $mobile): string
    {
        if (strlen($mobile) < 7) {
            return '***';
        }

        return substr($mobile, 0, 4).str_repeat('*', max(strlen($mobile) - 7, 3)).substr($mobile, -3);
    }

    private function durationMs(float $startedAt, float $finishedAt): float
    {
        return round(($finishedAt - $startedAt) * 1000, 2);
    }
}
