<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Services\PaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly PaymentMethodService $paymentMethodService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'driver', 'is_active', 'rows']);
        $rows = (int) ($filters['rows'] ?? 15);

        return Inertia::render('PaymentMethods/Index', [
            'paymentMethods' => $this->paymentMethodService->paginate($filters, $rows)
                ->through(fn (PaymentMethod $item) => PaymentMethodResource::make($item)->resolve()),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('PaymentMethods/Create');
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $this->paymentMethodService->create($request->validated());

        return redirect()->route('admin.payment-methods.index')->with('success', 'روش پرداخت با موفقیت ایجاد شد.');
    }

    public function edit(PaymentMethod $paymentMethod): Response
    {
        return Inertia::render('PaymentMethods/Edit', [
            'paymentMethod' => PaymentMethodResource::make($paymentMethod),
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->paymentMethodService->update($paymentMethod, $request->validated());

        return redirect()->route('admin.payment-methods.index')->with('success', 'روش پرداخت با موفقیت ویرایش شد.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->paymentMethodService->delete($paymentMethod);

        return back()->with('success', 'روش پرداخت حذف شد.');
    }

    public function toggleStatus(PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->paymentMethodService->toggleStatus($paymentMethod);

        return back()->with('success', 'وضعیت روش پرداخت به‌روزرسانی شد.');
    }

    public function options(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'driver' => ['nullable', 'in:zarinpal,idpay,nextpay,card_to_card,cash_on_delivery,wallet,manual'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($this->paymentMethodService->options($request->only(['q', 'driver', 'limit'])));
    }
}

