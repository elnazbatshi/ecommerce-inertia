<?php

namespace App\Services;

use App\Http\Services\OrderInventoryService;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Data\PaymentRequestData;
use App\Payments\Data\PaymentVerificationData;
use App\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FrontendPaymentService
{
    public const ONLINE_DRIVERS = ['zarinpal', 'idpay', 'nextpay', 'aqayepardakht', 'digipay', 'snappay', 'tara'];

    public function __construct(
        private readonly OrderInventoryService $inventory,
        private readonly PaymentGatewayManager $gateways,
    )
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
            'payment_method_id' => $order->payment_method_id,
            'amount' => $order->total,
            'currency' => 'IRR',
            'method' => $this->methodForDriver($driver),
            'gateway' => $driver,
            'transaction_id' => $this->transactionId($order),
            'status' => 'pending',
            'initiated_at' => now(),
        ]);
    }

    public function initiate(Payment $payment): ?string
    {
        $payment->loadMissing(['order.customer', 'order.paymentMethod']);
        $driver = $this->gateways->driver($payment->gateway);

        if (! $this->isOnline($payment)) {
            return null;
        }

        $result = $driver->initiate(new PaymentRequestData(
            orderId: $payment->order_id,
            orderNumber: $payment->order->order_number,
            amount: (float) $payment->amount,
            currency: $payment->currency ?: 'IRR',
            callbackUrl: route('frontend.payments.callback', ['provider' => $payment->gateway]),
            customerName: $payment->order->customer?->name,
            customerPhone: $payment->order->customer?->phone,
            customerEmail: $payment->order->customer?->email,
            description: "Order {$payment->order->order_number}",
            metadata: ['payment_transaction_id' => $payment->transaction_id],
        ));

        $payment->forceFill([
            'authority' => $result->authority,
            'transaction_id' => $result->providerTransactionId ?: $payment->transaction_id,
            'response_payload' => $result->rawResponse,
            'error_code' => $result->errorCode,
            'error_message' => $result->errorMessage,
        ])->save();

        if (! $result->success || ! $result->redirectUrl) {
            throw ValidationException::withMessages([
                'payment' => $result->errorMessage ?: 'شروع پرداخت ناموفق بود.',
            ]);
        }

        return $result->redirectUrl;
    }

    public function verifyCallback(string $provider, array $payload): Payment
    {
        if (! in_array($provider, PaymentGatewayManager::PROVIDERS, true)) {
            abort(404);
        }

        $authority = $payload['transid'] ?? $payload['authority'] ?? $payload['token'] ?? $payload['Authority'] ?? null;

        if (! $authority) {
            throw ValidationException::withMessages(['payment' => 'شناسه پرداخت در callback موجود نیست.']);
        }

        return DB::transaction(function () use ($provider, $payload, $authority) {
            $payment = Payment::query()
                ->where('gateway', $provider)
                ->where(function ($query) use ($authority) {
                    $query->where('authority', $authority)
                        ->orWhere('transaction_id', $authority);
                })
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'paid') {
                return $payment;
            }

            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($payment->order_id);
            $driver = $this->gateways->driver($provider);
            $result = $driver->verify(new PaymentVerificationData(
                paymentId: $payment->id,
                orderId: $order->id,
                provider: $provider,
                amount: (float) $payment->amount,
                authority: $payment->authority ?: $authority,
                transactionId: $payment->transaction_id,
                referenceId: $payment->reference_id,
                callbackPayload: $payload,
            ));

            if (! $result->success || round((float) $result->paidAmount, 2) !== round((float) $order->total, 2)) {
                $payment->forceFill([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'callback_payload' => $payload,
                    'response_payload' => $result->rawResponse,
                    'error_code' => $result->errorCode ?: 'amount_mismatch',
                    'error_message' => $result->errorMessage ?: 'مبلغ تاییدشده با سفارش برابر نیست.',
                ])->save();

                $order->forceFill([
                    'payment_status' => 'failed',
                    'status' => 'cancelled',
                    'cancelled_at' => $order->cancelled_at ?? now(),
                ])->save();

                $this->inventory->release($order->refresh(), null, 'return');

                return $payment->refresh();
            }

            $payment->forceFill([
                'status' => 'paid',
                'transaction_id' => $result->transactionId ?: $payment->transaction_id,
                'reference_id' => $result->referenceId ?: $payment->reference_id,
                'paid_at' => $result->paidAt ?: now(),
                'verified_at' => now(),
                'callback_payload' => $payload,
                'response_payload' => $result->rawResponse,
                'raw_response' => $result->rawResponse,
                'error_code' => null,
                'error_message' => null,
            ])->save();

            $this->inventory->confirm($order->refresh());

            return $payment->refresh();
        });
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
                'verified_at' => now(),
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
                'failed_at' => now(),
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
            'zarinpal', 'idpay', 'nextpay', 'aqayepardakht', 'digipay', 'snappay', 'tara' => 'online',
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
