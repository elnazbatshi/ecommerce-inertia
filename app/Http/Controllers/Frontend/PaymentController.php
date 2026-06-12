<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\FrontendPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(private readonly FrontendPaymentService $payments)
    {
    }

    public function start(Request $request, Payment $payment): RedirectResponse|Response
    {
        $this->authorizeCustomer($request, $payment);

        $payment->load(['order.paymentMethod']);

        if ($payment->status !== 'pending') {
            return $this->redirectForStatus($payment);
        }

        return Inertia::render('Frontend/Payments/FakeGateway', [
            'payment' => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'gateway' => $payment->gateway,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
            ],
            'order' => [
                'id' => $payment->order->id,
                'order_number' => $payment->order->order_number,
                'total' => (float) $payment->order->total,
                'payment_method' => [
                    'name' => $payment->order->paymentMethod?->name,
                    'driver' => $payment->order->paymentMethod?->driver,
                ],
            ],
            'routes' => [
                'success' => route('frontend.payments.fake.success', $payment),
                'fail' => route('frontend.payments.fake.fail', $payment),
                'cart' => route('site.cart'),
            ],
        ]);
    }

    public function fakeSuccess(Request $request, Payment $payment): RedirectResponse
    {
        $this->guardFakeGateway();
        $this->authorizeCustomer($request, $payment);

        $this->payments->markPaid($payment, ['fake' => true, 'result' => 'success']);

        return redirect()->route('frontend.orders.thank-you', $payment->fresh()->order);
    }

    public function fakeFail(Request $request, Payment $payment): RedirectResponse
    {
        $this->guardFakeGateway();
        $this->authorizeCustomer($request, $payment);

        $this->payments->markFailed($payment, ['fake' => true, 'result' => 'failed']);

        return redirect()->route('site.cart');
    }

    private function authorizeCustomer(Request $request, Payment $payment): Customer
    {
        $customerId = $request->session()->get('customer_id');
        $customer = $customerId ? Customer::find($customerId) : null;

        if (! $customer || (int) $payment->customer_id !== (int) $customer->id) {
            abort(403);
        }

        return $customer;
    }

    private function guardFakeGateway(): void
    {
        if (app()->environment('production')) {
            abort(404);
        }
    }

    private function redirectForStatus(Payment $payment): RedirectResponse
    {
        if ($payment->status === 'paid') {
            return redirect()->route('frontend.orders.thank-you', $payment->order);
        }

        return redirect()->route('site.cart');
    }
}
