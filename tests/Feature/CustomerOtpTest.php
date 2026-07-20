<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerOtp;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CustomerOtpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'sms.fake' => true,
            'sms.smsir.send_mode' => 'text',
        ]);
    }

    public function test_fake_otp_request_logs_code_without_returning_it_and_sends_no_http_request(): void
    {
        Log::spy();
        Http::fake();

        $response = $this->postJson('/customer/auth/otp', [
            'phone' => '09120001001',
        ]);

        $response->assertOk()
            ->assertJsonPath('mode', 'register')
            ->assertJsonPath('expires_in', 120)
            ->assertJsonMissingPath('debug_code');

        Log::shouldHaveReceived('info')
            ->with('Fake customer OTP SMS.', \Mockery::on(fn (array $context) => $context['mobile'] === '09120001001'));

        $this->assertSame(1, CustomerOtp::where('phone', '09120001001')->count());
        Http::assertNothingSent();
    }

    public function test_text_mode_sends_smsir_bulk_request_with_message_and_line_number(): void
    {
        config([
            'sms.fake' => false,
            'sms.smsir.send_mode' => 'text',
            'sms.smsir.api_key' => 'test-api-key',
            'sms.smsir.line_number' => '300000000000',
        ]);

        Http::fake([
            'api.sms.ir/*' => Http::response(['status' => true], 200),
        ]);

        $response = $this->postJson('/customer/auth/otp', [
            'phone' => '09120001002',
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return str_ends_with($request->url(), '/send/bulk')
                && $request->hasHeader('X-API-KEY', 'test-api-key')
                && $payload['lineNumber'] === '300000000000'
                && $payload['mobiles'] === ['09120001002']
                && str_contains($payload['messageText'], 'کد ورود به موتوشهر:')
                && str_contains($payload['messageText'], 'این کد را در اختیار دیگران قرار ندهید.')
                && ! array_key_exists('templateId', $payload);
        });
    }

    public function test_text_mode_requires_line_number(): void
    {
        config([
            'sms.fake' => false,
            'sms.smsir.send_mode' => 'text',
            'sms.smsir.api_key' => 'test-api-key',
            'sms.smsir.line_number' => null,
        ]);

        Http::fake();

        $response = $this->postJson('/customer/auth/otp', [
            'phone' => '09120001005',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        $this->assertSame(1, CustomerOtp::where('phone', '09120001005')->count());
        Http::assertNothingSent();
    }

    public function test_verify_mode_keeps_smsir_template_request_payload(): void
    {
        config([
            'sms.fake' => false,
            'sms.smsir.send_mode' => 'verify',
            'sms.smsir.api_key' => 'test-api-key',
            'sms.smsir.template_otp' => '123456',
        ]);

        Http::fake([
            'api.sms.ir/*' => Http::response(['status' => true], 200),
        ]);

        $response = $this->postJson('/customer/auth/otp', [
            'phone' => '09120001006',
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return str_ends_with($request->url(), '/send/verify')
                && $request->hasHeader('X-API-KEY', 'test-api-key')
                && $payload['mobile'] === '09120001006'
                && $payload['templateId'] === 123456
                && $payload['parameters'][0]['name'] === 'CODE'
                && isset($payload['parameters'][0]['value'])
                && ! array_key_exists('message', $payload);
        });
    }

    public function test_verify_mode_requires_template_id(): void
    {
        config([
            'sms.fake' => false,
            'sms.smsir.send_mode' => 'verify',
            'sms.smsir.api_key' => 'test-api-key',
            'sms.smsir.template_otp' => null,
        ]);

        Http::fake();

        $response = $this->postJson('/customer/auth/otp', [
            'phone' => '09120001007',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        $this->assertSame(1, CustomerOtp::where('phone', '09120001007')->count());
        Http::assertNothingSent();
    }

    public function test_sms_api_error_stores_hashed_otp_without_returning_code(): void
    {
        $phone = '09120001003';

        CustomerOtp::create([
            'phone' => $phone,
            'code' => Hash::make('111111'),
            'expires_at' => now()->addMinutes(2),
        ]);

        config([
            'sms.fake' => false,
            'sms.smsir.send_mode' => 'text',
            'sms.smsir.api_key' => 'test-api-key',
            'sms.smsir.line_number' => '300000000000',
        ]);

        Http::fake([
            'api.sms.ir/*' => Http::response(['status' => false], 500),
        ]);

        $response = $this->postJson('/customer/auth/otp', [
            'phone' => $phone,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('phone')
            ->assertJsonMissingPath('debug_code');

        $this->assertSame(1, CustomerOtp::where('phone', $phone)->count());
        $this->assertFalse(Hash::check('111111', CustomerOtp::where('phone', $phone)->first()->code));
    }

    public function test_otp_request_is_rate_limited_per_phone(): void
    {
        $phone = '09120001004';

        $this->postJson('/customer/auth/otp', ['phone' => $phone])->assertOk();

        $this->postJson('/customer/auth/otp', ['phone' => $phone])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        for ($i = 0; $i < 4; $i++) {
            $this->travel(61)->seconds();
            $this->postJson('/customer/auth/otp', ['phone' => $phone])->assertOk();
        }

        $this->travel(61)->seconds();

        $this->postJson('/customer/auth/otp', ['phone' => $phone])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_successful_verify_sets_customer_session_and_deletes_phone_otps(): void
    {
        $phone = '09120000001';

        CustomerOtp::create([
            'phone' => $phone,
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(2),
        ]);

        CustomerOtp::create([
            'phone' => $phone,
            'code' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(2),
            'verified_at' => now(),
        ]);

        $response = $this->postJson('/customer/auth/verify', [
            'phone' => $phone,
            'code' => '123456',
            'name' => 'Customer Test',
        ]);

        $response->assertOk();
        $this->assertNotNull(session('customer_id'));
        $this->assertDatabaseHas('customers', [
            'id' => session('customer_id'),
            'phone' => $phone,
        ]);
        $this->assertSame(0, CustomerOtp::where('phone', $phone)->count());
    }

    public function test_requesting_new_otp_deletes_previous_phone_otps(): void
    {
        $phone = '09120000002';

        CustomerOtp::create([
            'phone' => $phone,
            'code' => Hash::make('111111'),
            'expires_at' => now()->addMinutes(2),
        ]);

        CustomerOtp::create([
            'phone' => '09120000003',
            'code' => Hash::make('222222'),
            'expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->postJson('/customer/auth/otp', [
            'phone' => $phone,
        ]);

        $response->assertOk();
        $this->assertSame(1, CustomerOtp::where('phone', $phone)->count());
        $this->assertSame(1, CustomerOtp::where('phone', '09120000003')->count());
    }

    public function test_prune_command_deletes_expired_otps_and_keeps_valid_otps(): void
    {
        CustomerOtp::create([
            'phone' => '09120000004',
            'code' => Hash::make('111111'),
            'expires_at' => now()->subMinute(),
        ]);

        CustomerOtp::create([
            'phone' => '09120000005',
            'code' => Hash::make('222222'),
            'expires_at' => now()->addMinute(),
        ]);

        $expiredCount = CustomerOtp::where('expires_at', '<', now())->count();

        $this->artisan('customer-otps:prune')
            ->expectsOutput("Deleted {$expiredCount} expired customer OTP records.")
            ->assertExitCode(0);

        $this->assertSame(0, CustomerOtp::where('phone', '09120000004')->count());
        $this->assertSame(1, CustomerOtp::where('phone', '09120000005')->count());
    }

    public function test_successful_verify_uses_existing_customer_without_touching_dashboard_user_auth(): void
    {
        $customer = Customer::create([
            'name' => 'Existing Customer',
            'phone' => '09120000006',
            'email' => 'existing-customer@example.test',
            'status' => 'active',
        ]);

        CustomerOtp::create([
            'phone' => $customer->phone,
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->postJson('/customer/auth/verify', [
            'phone' => $customer->phone,
            'code' => '123456',
        ]);

        $response->assertOk();
        $this->assertSame($customer->id, session('customer_id'));
        $this->assertGuest();
        $this->assertSame(0, CustomerOtp::where('phone', $customer->phone)->count());
    }
}
