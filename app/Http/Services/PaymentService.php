<?php

namespace App\Http\Services;

use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private readonly OrderInventoryService $inventory)
    {
    }

    public function paginated(Request $request)
    {
        return Payment::query()
            ->with(['order:id,order_number,total,payment_status,status', 'customer:id,name,phone'])
            ->search($request->string('search')->toString())
            ->filter($request->only(['status', 'method', 'gateway', 'date_from', 'date_to']))
            ->latest()
            ->paginate(Pagination::perPage($request))
            ->withQueryString()
            ->through(fn (Payment $payment) => PaymentResource::make($payment)->resolve());
    }

    public function orderOptions()
    {
        return Order::query()
            ->with('customer:id,name,phone')
            ->latest()
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total' => (float) $order->total,
                'customer' => $order->customer,
                'label' => "{$order->order_number} - " . ($order->customer?->phone ?? '-'),
            ]);
    }

    public function gatewayOptions()
    {
        return Payment::query()
            ->whereNotNull('gateway')
            ->distinct()
            ->orderBy('gateway')
            ->pluck('gateway')
            ->map(fn (string $gateway) => ['label' => $gateway, 'value' => $gateway])
            ->values();
    }

    public function create(array $data, ?int $userId): Payment
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = Order::query()->lockForUpdate()->findOrFail($data['order_id']);
            $payment = Payment::create([
                ...$data,
                'customer_id' => $order->customer_id,
                'paid_at' => $data['status'] === 'paid' ? now() : null,
            ]);

            $this->syncOrder($payment, $userId);

            return $payment;
        });
    }

    public function update(Payment $payment, array $data, ?int $userId): void
    {
        DB::transaction(function () use ($payment, $data, $userId) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($data['order_id']);

            $payment->update([
                ...$data,
                'customer_id' => $order->customer_id,
                'paid_at' => $data['status'] === 'paid' ? ($payment->paid_at ?? now()) : null,
            ]);

            $this->syncOrder($payment->refresh(), $userId);
        });
    }

    public function updateStatus(Payment $payment, string $status, ?int $userId): void
    {
        DB::transaction(function () use ($payment, $status, $userId) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $payment->update([
                'status' => $status,
                'paid_at' => $status === 'paid' ? ($payment->paid_at ?? now()) : null,
            ]);

            $this->syncOrder($payment->refresh(), $userId);
        });
    }

    public function refund(Payment $payment, ?int $userId): void
    {
        DB::transaction(function () use ($payment, $userId) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $payment->update(['status' => 'refunded']);
            $this->syncOrder($payment->refresh(), $userId, true);
        });
    }

    private function syncOrder(Payment $payment, ?int $userId, bool $forceRefund = false): void
    {
        $order = Order::query()->lockForUpdate()->with('items')->findOrFail($payment->order_id);

        if ($payment->status === 'paid') {
            $order->forceFill([
                'payment_status' => 'paid',
                'paid_at' => $order->paid_at ?? now(),
            ])->save();

            $this->inventory->sync($order->refresh(), $userId);
            return;
        }

        if (in_array($payment->status, ['failed', 'cancelled'], true)) {
            $order->forceFill(['payment_status' => 'failed'])->save();
            return;
        }

        if ($payment->status === 'refunded' || $forceRefund) {
            $order->forceFill([
                'payment_status' => 'refunded',
                'status' => $order->status === 'delivered' ? 'returned' : 'cancelled',
                'cancelled_at' => $order->status === 'delivered' ? $order->cancelled_at : ($order->cancelled_at ?? now()),
            ])->save();

            $this->inventory->sync($order->refresh(), $userId);
        }
    }
}
