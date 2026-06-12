<?php

namespace App\Services;

use App\Http\Services\OrderInventoryService;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FrontendPaymentService
{
    public const ONLINE_DRIVERS = ['zarinpal', 'idpay', 'nextpay'];

    public function __construct(private readonly OrderInventoryService $inventory)
    {
    }

    public function createPendingPayment(Order $order): Payment
    {
        $order->loadMissing('paymentMethod');

        $pending = $order->payments()
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($pending) {
            return $pending;
        }

        $driver = $order->paymentMethod?->driver ?: 'manual';

        return Payment::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'amount' => $order->total,
            'method' => $this->methodForDriver($driver),
            'gateway' => $driver,
            'transaction_id' => $this->transactionId($order),
            'status' => 'pending',
        ]);
    }

    public function markPaid(Payment $payment, array $payload = []): void
    {
        DB::transaction(function () use ($payment, $payload) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === 'paid') {
                return;
            }

            if (in_array($payment->status, ['failed', 'cancelled', 'refunded'], true)) {
                return;
            }

            $payment->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'raw_response' => $payload,
            ])->save();

            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($payment->order_id);
            $this->inventory->confirm($order);
        });
    }

    public function markFailed(Payment $payment, array $payload = []): void
    {
        DB::transaction(function () use ($payment, $payload) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === 'paid') {
                return;
            }

            if (in_array($payment->status, ['failed', 'cancelled'], true)) {
                return;
            }

            $payment->forceFill([
                'status' => 'failed',
                'raw_response' => $payload,
            ])->save();

            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($payment->order_id);
            $order->forceFill([
                'payment_status' => 'failed',
                'status' => 'cancelled',
                'cancelled_at' => $order->cancelled_at ?? now(),
            ])->save();

            $this->inventory->release($order->refresh(), null, 'return');
        });
    }

    public function isOnline(Order|Payment $model): bool
    {
        $driver = $model instanceof Payment
            ? $model->gateway
            : $model->paymentMethod?->driver;

        return in_array($driver, self::ONLINE_DRIVERS, true);
    }

    private function methodForDriver(string $driver): string
    {
        return match ($driver) {
            'zarinpal', 'idpay', 'nextpay' => 'online',
            'cash_on_delivery' => 'cash',
            'wallet' => 'wallet',
            default => 'card_to_card',
        };
    }

    private function transactionId(Order $order): string
    {
        do {
            $id = 'PAY-' . $order->id . '-' . now()->timestamp . '-' . Str::upper(Str::random(8));
        } while (Payment::query()->where('transaction_id', $id)->exists());

        return $id;
    }
}
