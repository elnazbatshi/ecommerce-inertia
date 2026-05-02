<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderInventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(private readonly OrderInventoryService $inventory)
    {
    }

    public function index(Request $request): Response
    {
        $perPage = max(1, min((int) $request->integer('rows', 10), 100));

        $payments = Payment::query()
            ->with(['order:id,order_number,total,payment_status,status', 'customer:id,name,phone'])
            ->when($request->string('search')->toString(), function (Builder $query, string $search) {
                $query->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('reference_id', 'like', "%{$search}%")
                    ->orWhereHas('order', fn (Builder $orderQuery) => $orderQuery->where('order_number', 'like', "%{$search}%"))
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            })
            ->when($request->input('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->input('method'), fn (Builder $query, string $method) => $query->where('method', $method))
            ->when($request->input('gateway'), fn (Builder $query, string $gateway) => $query->where('gateway', $gateway))
            ->when($request->input('date_from'), fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->input('date_to'), fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Payment $payment) => $this->formatPayment($payment));

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'status', 'method', 'gateway', 'date_from', 'date_to', 'rows']),
            'orders' => $this->orderOptions(),
            'statusOptions' => $this->statusOptions(),
            'methodOptions' => $this->methodOptions(),
            'gatewayOptions' => $this->gatewayOptions(),
        ]);
    }

    public function show(Payment $payment): Response
    {
        return Inertia::render('Payments/Show', [
            'payment' => $this->formatPayment($this->loadPayment($payment), true),
            'statusOptions' => $this->statusOptions(),
            'methodOptions' => $this->methodOptions(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = DB::transaction(function () use ($request) {
            $order = Order::query()->lockForUpdate()->findOrFail($request->validated('order_id'));
            $payment = Payment::create([
                ...$request->validated(),
                'customer_id' => $order->customer_id,
                'paid_at' => $request->validated('status') === 'paid' ? now() : null,
            ]);

            $this->syncOrderFromPayment($payment, $request->user()?->id);

            return $payment;
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment created successfully.');
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        DB::transaction(function () use ($request, $payment) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($request->validated('order_id'));

            $payment->update([
                ...$request->validated(),
                'customer_id' => $order->customer_id,
                'paid_at' => $request->validated('status') === 'paid'
                    ? ($payment->paid_at ?? now())
                    : null,
            ]);

            $this->syncOrderFromPayment($payment->refresh(), $request->user()?->id);
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }

    public function changeStatus(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(Payment::STATUSES)]]);

        DB::transaction(function () use ($payment, $data, $request) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $payment->update([
                'status' => $data['status'],
                'paid_at' => $data['status'] === 'paid' ? ($payment->paid_at ?? now()) : null,
            ]);

            $this->syncOrderFromPayment($payment->refresh(), $request->user()?->id);
        });

        return back()->with('success', 'Payment status updated successfully.');
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        DB::transaction(function () use ($payment, $request) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $payment->update(['status' => 'refunded']);
            $this->syncOrderFromPayment($payment->refresh(), $request->user()?->id, true);
        });

        return back()->with('success', 'Payment refunded successfully.');
    }

    private function syncOrderFromPayment(Payment $payment, ?int $userId, bool $forceRefund = false): void
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

        if ($payment->status === 'failed') {
            $order->forceFill(['payment_status' => 'failed'])->save();
            return;
        }

        if ($payment->status === 'cancelled') {
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

    private function loadPayment(Payment $payment): Payment
    {
        return $payment->load(['order.customer', 'order.items', 'customer']);
    }

    private function orderOptions()
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

    private function statusOptions(): array
    {
        return [
            ['label' => 'Pending', 'value' => 'pending', 'severity' => 'warn'],
            ['label' => 'Paid', 'value' => 'paid', 'severity' => 'success'],
            ['label' => 'Failed', 'value' => 'failed', 'severity' => 'danger'],
            ['label' => 'Cancelled', 'value' => 'cancelled', 'severity' => 'secondary'],
            ['label' => 'Refunded', 'value' => 'refunded', 'severity' => 'info'],
        ];
    }

    private function methodOptions(): array
    {
        return [
            ['label' => 'Online', 'value' => 'online'],
            ['label' => 'Card to card', 'value' => 'card_to_card'],
            ['label' => 'Cash', 'value' => 'cash'],
            ['label' => 'Wallet', 'value' => 'wallet'],
        ];
    }

    private function gatewayOptions()
    {
        return Payment::query()
            ->whereNotNull('gateway')
            ->distinct()
            ->orderBy('gateway')
            ->pluck('gateway')
            ->map(fn (string $gateway) => ['label' => $gateway, 'value' => $gateway])
            ->values();
    }

    private function formatPayment(Payment $payment, bool $full = false): array
    {
        $data = [
            'id' => $payment->id,
            'order_id' => $payment->order_id,
            'order' => $payment->order ? [
                'id' => $payment->order->id,
                'order_number' => $payment->order->order_number,
                'total' => $payment->order->total,
                'status' => $payment->order->status,
                'payment_status' => $payment->order->payment_status,
                'customer' => $payment->order->customer,
            ] : null,
            'customer' => $payment->customer,
            'amount' => $payment->amount,
            'method' => $payment->method,
            'gateway' => $payment->gateway,
            'transaction_id' => $payment->transaction_id,
            'reference_id' => $payment->reference_id,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at?->toDateTimeString(),
            'admin_note' => $payment->admin_note,
            'created_at' => $payment->created_at?->toDateTimeString(),
        ];

        if ($full) {
            $data['raw_response'] = $payment->raw_response;
        }

        return $data;
    }
}
