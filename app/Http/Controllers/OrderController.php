<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeOrderPaymentStatusRequest;
use App\Http\Requests\ChangeOrderStatusRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Orders/Index', [
            'orders' => $this->orders->paginated($request),
            'filters' => $request->only(['search', 'status', 'payment_status', 'date_from', 'date_to', 'rows']),
            'statusOptions' => config('shop.orders.status_options'),
            'paymentStatusOptions' => config('shop.orders.payment_status_options'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Orders/Create', $this->formData());
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->orders->create($request->validated(), $request->user()?->id);

        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function show(Order $order): Response
    {
        return Inertia::render('Orders/Show', [
            'order' => OrderResource::make($this->loadOrder($order))->resolve(),
            'statusOptions' => config('shop.orders.status_options'),
            'paymentStatusOptions' => config('shop.orders.payment_status_options'),
        ]);
    }

    public function edit(Order $order): Response
    {
        return Inertia::render('Orders/Edit', [
            ...$this->formData(),
            'order' => OrderResource::make($this->loadOrder($order))->resolve(),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->orders->update($order, $request->validated(), $request->user()?->id);

        return redirect()->route('orders.show', $order)->with('success', 'Order updated successfully.');
    }

    public function destroy(Request $request, Order $order): RedirectResponse
    {
        $this->orders->delete($order, $request->user()?->id);

        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }

    public function changeStatus(ChangeOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $this->orders->updateStatus($order, $request->validated('status'), $request->user()?->id);

        return back()->with('success', 'Order status updated successfully.');
    }

    public function changePaymentStatus(ChangeOrderPaymentStatusRequest $request, Order $order): RedirectResponse
    {
        $this->orders->updatePaymentStatus($order, $request->validated('payment_status'), $request->user()?->id);

        return back()->with('success', 'Payment status updated successfully.');
    }

    private function formData(): array
    {
        return [
            'customers' => $this->orders->customerOptions(),
            'products' => $this->orders->productOptions(),
            'statusOptions' => config('shop.orders.status_options'),
            'paymentStatusOptions' => config('shop.orders.payment_status_options'),
        ];
    }

    private function loadOrder(Order $order): Order
    {
        return $order->load(['customer', 'address', 'items.product', 'items.productVariant']);
    }
}
