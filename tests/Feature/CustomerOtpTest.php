<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerOtp;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerOtpTest extends TestCase
{
    use DatabaseTransactions;

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
