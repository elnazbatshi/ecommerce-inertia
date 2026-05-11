<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePaymentStatusRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Services\PaymentService;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Payments/Index', [
            'payments' => $this->payments->paginated($request),
            'filters' => $request->only(['search', 'status', 'method', 'gateway', 'date_from', 'date_to', 'rows']),
            'orders' => $this->payments->orderOptions(),
            'statusOptions' => config('shop.payments.status_options'),
            'methodOptions' => config('shop.payments.method_options'),
            'gatewayOptions' => $this->payments->gatewayOptions(),
        ]);
    }

    public function show(Payment $payment): Response
    {
        return Inertia::render('Payments/Show', [
            'payment' => PaymentResource::make($this->loadPayment($payment))->resolve(),
            'statusOptions' => config('shop.payments.status_options'),
            'methodOptions' => config('shop.payments.method_options'),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = $this->payments->create($request->validated(), $request->user()?->id);

        return redirect()->route('payments.show', $payment)->with('success', 'Payment created successfully.');
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->payments->update($payment, $request->validated(), $request->user()?->id);

        return redirect()->route('payments.show', $payment)->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }

    public function changeStatus(ChangePaymentStatusRequest $request, Payment $payment): RedirectResponse
    {
        $this->payments->updateStatus($payment, $request->validated('status'), $request->user()?->id);

        return back()->with('success', 'Payment status updated successfully.');
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $this->payments->refund($payment, $request->user()?->id);

        return back()->with('success', 'Payment refunded successfully.');
    }

    private function loadPayment(Payment $payment): Payment
    {
        return $payment->load(['order.customer', 'order.items', 'customer']);
    }
}
