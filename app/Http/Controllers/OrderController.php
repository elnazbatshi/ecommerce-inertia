<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = max(1, min((int) $request->integer('rows', 10), 100));

        $orders = Order::query()
            ->with('customer:id,name,phone')
            ->withCount('items')
            ->when($request->string('search')->toString(), function (Builder $query, string $search) {
                $query->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            })
            ->when($request->input('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->input('payment_status'), fn (Builder $query, string $status) => $query->where('payment_status', $status))
            ->when($request->input('date_from'), fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->input('date_to'), fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Order $order) => $this->formatOrderSummary($order));

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'payment_status', 'date_from', 'date_to', 'rows']),
            'statusOptions' => $this->statusOptions(),
            'paymentStatusOptions' => $this->paymentStatusOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Orders/Create', $this->formData());
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = DB::transaction(function () use ($request) {
            $payload = $this->orderPayload($request->validated());
            $order = Order::create([
                ...$payload['order'],
                'order_number' => $this->generateOrderNumber(),
            ]);

            $this->syncItems($order, $payload['items']);
            $this->syncInventoryState($order->refresh(), $request->user()?->id);

            return $order;
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function show(Order $order): Response
    {
        return Inertia::render('Orders/Show', [
            'order' => $this->formatOrder($this->loadOrder($order)),
            'statusOptions' => $this->statusOptions(),
            'paymentStatusOptions' => $this->paymentStatusOptions(),
        ]);
    }

    public function edit(Order $order): Response
    {
        return Inertia::render('Orders/Edit', [
            ...$this->formData(),
            'order' => $this->formatOrder($this->loadOrder($order)),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($request, $order) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->inventory_reduced_at && ! $order->inventory_returned_at) {
                $this->returnInventory($order, $request->user()?->id, 'correction');
                $order->forceFill(['inventory_reduced_at' => null])->save();
            }

            $payload = $this->orderPayload($request->validated());
            $order->update($payload['order']);
            $order->items()->delete();
            $this->syncItems($order, $payload['items']);
            $this->syncInventoryState($order->refresh(), $request->user()?->id);
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order updated successfully.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->inventory_reduced_at && ! $order->inventory_returned_at) {
                $this->returnInventory($order, request()->user()?->id, 'return');
            }

            $order->delete();
        });

        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }

    public function changeStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(Order::STATUSES)]]);

        DB::transaction(function () use ($order, $data, $request) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $order->update($this->statusTimestamps($order, ['status' => $data['status']]));
            $this->syncInventoryState($order->refresh(), $request->user()?->id);
        });

        return back()->with('success', 'Order status updated successfully.');
    }

    public function changePaymentStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['payment_status' => ['required', Rule::in(Order::PAYMENT_STATUSES)]]);

        DB::transaction(function () use ($order, $data, $request) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $order->update($this->statusTimestamps($order, ['payment_status' => $data['payment_status']]));
            $this->syncInventoryState($order->refresh(), $request->user()?->id);
        });

        return back()->with('success', 'Payment status updated successfully.');
    }

    private function formData(): array
    {
        return [
            'customers' => Customer::query()
                ->with('addresses')
                ->orderBy('name')
                ->get()
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'addresses' => $customer->addresses->map(fn ($address) => [
                        'id' => $address->id,
                        'title' => $address->title,
                        'receiver_name' => $address->receiver_name,
                        'receiver_phone' => $address->receiver_phone,
                        'province' => $address->province,
                        'city' => $address->city,
                        'address' => $address->address,
                        'is_default' => $address->is_default,
                        'label' => trim(($address->title ?: $address->city) . ' - ' . $address->address),
                    ])->values(),
                ]),
            'products' => $this->productOptions(),
            'statusOptions' => $this->statusOptions(),
            'paymentStatusOptions' => $this->paymentStatusOptions(),
        ];
    }

    private function orderPayload(array $data): array
    {
        $items = collect($data['items'])->map(function (array $item) {
            $product = Product::query()->with('variants.attributeValues.attribute')->findOrFail($item['product_id']);
            $variant = filled($item['product_variant_id'] ?? null)
                ? ProductVariant::query()->with('attributeValues.attribute')->findOrFail($item['product_variant_id'])
                : null;

            $unitPrice = (float) $item['unit_price'];
            $discountPrice = filled($item['discount_price'] ?? null) ? (float) $item['discount_price'] : null;
            $linePrice = $discountPrice ?? $unitPrice;

            return [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name' => $product->name,
                'variant_name' => $variant ? $this->variantLabel($variant) : null,
                'sku' => $variant?->sku ?: $product->sku,
                'quantity' => (int) $item['quantity'],
                'unit_price' => $unitPrice,
                'discount_price' => $discountPrice,
                'total_price' => $linePrice * (int) $item['quantity'],
            ];
        })->values();

        $subtotal = $items->sum(fn ($item) => $item['unit_price'] * $item['quantity']);
        $itemDiscount = $items->sum(fn ($item) => max(0, ($item['unit_price'] - ($item['discount_price'] ?? $item['unit_price'])) * $item['quantity']));
        $manualDiscount = (float) ($data['discount_total'] ?? 0);
        $shipping = (float) ($data['shipping_cost'] ?? 0);
        $tax = (float) ($data['tax_total'] ?? 0);
        $discountTotal = $itemDiscount + $manualDiscount;
        $total = max(0, $subtotal - $discountTotal + $shipping + $tax);

        return [
            'order' => $this->statusTimestamps(null, [
                'customer_id' => $data['customer_id'],
                'address_id' => $data['address_id'] ?? null,
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_cost' => $shipping,
                'tax_total' => $tax,
                'total' => $total,
                'customer_note' => $data['customer_note'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
            ]),
            'items' => $items,
        ];
    }

    private function syncItems(Order $order, $items): void
    {
        foreach ($items as $item) {
            $order->items()->create($item);
        }
    }

    private function syncInventoryState(Order $order, ?int $userId): void
    {
        $shouldReduce = ($order->payment_status === 'paid' || in_array($order->status, ['processing', 'shipped', 'delivered'], true))
            && ! in_array($order->status, ['cancelled', 'returned'], true);

        if ($shouldReduce && ! $order->inventory_reduced_at) {
            $this->reduceInventory($order, $userId);
            $order->forceFill(['inventory_reduced_at' => now(), 'inventory_returned_at' => null])->save();
        }

        if (in_array($order->status, ['cancelled', 'returned'], true) && $order->inventory_reduced_at && ! $order->inventory_returned_at) {
            $this->returnInventory($order, $userId, $order->status === 'returned' ? 'return' : 'correction');
        }
    }

    private function reduceInventory(Order $order, ?int $userId): void
    {
        foreach ($order->items as $item) {
            [$stockable, $productId, $variantId] = $this->stockableForItem($item);
            $previous = (int) ($stockable->stock ?? 0);

            if ($previous - $item->quantity < 0) {
                throw ValidationException::withMessages(['items' => "Not enough stock for {$item->product_name}."]);
            }

            $new = $previous - $item->quantity;
            $stockable->update(['stock' => $new]);
            $this->logInventory($productId, $variantId, 'order', $item->quantity, $previous, $new, "Order {$order->order_number}", $userId);
        }
    }

    private function returnInventory(Order $order, ?int $userId, string $type): void
    {
        foreach ($order->items as $item) {
            [$stockable, $productId, $variantId] = $this->stockableForItem($item);
            $previous = (int) ($stockable->stock ?? 0);
            $new = $previous + $item->quantity;
            $stockable->update(['stock' => $new]);
            $this->logInventory($productId, $variantId, $type, $item->quantity, $previous, $new, "Order {$order->order_number}", $userId);
        }

        $order->forceFill(['inventory_returned_at' => now()])->save();
    }

    private function stockableForItem(OrderItem $item): array
    {
        if ($item->product_variant_id) {
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($item->product_variant_id);
            return [$variant, null, $variant->id];
        }

        $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
        return [$product, $product->id, null];
    }

    private function logInventory(?int $productId, ?int $variantId, string $type, int $quantity, int $previous, int $new, string $note, ?int $userId): void
    {
        InventoryLog::create([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'type' => $type,
            'quantity' => $quantity,
            'previous_stock' => $previous,
            'new_stock' => $new,
            'note' => $note,
            'created_by' => $userId,
        ]);
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . now()->format('Ymd') . '-';
        $count = Order::query()->where('order_number', 'like', "{$prefix}%")->lockForUpdate()->count() + 1;

        do {
            $number = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    private function productOptions()
    {
        return Product::query()
            ->with('variants.attributeValues.attribute')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'discount_price' => $product->discount_price !== null ? (float) $product->discount_price : null,
                'stock' => (int) ($product->stock ?? 0),
                'label' => trim($product->name . ($product->sku ? " ({$product->sku})" : '')),
                'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => $variant->price !== null ? (float) $variant->price : (float) $product->price,
                    'discount_price' => $variant->discount_price !== null ? (float) $variant->discount_price : null,
                    'stock' => (int) $variant->stock,
                    'label' => $this->variantLabel($variant),
                ])->values(),
            ]);
    }

    private function loadOrder(Order $order): Order
    {
        return $order->load(['customer', 'address', 'items.product', 'items.productVariant']);
    }

    private function statusTimestamps(?Order $order, array $data): array
    {
        if (($data['payment_status'] ?? null) === 'paid' && ! $order?->paid_at) {
            $data['paid_at'] = now();
        }

        if (($data['status'] ?? null) === 'shipped' && ! $order?->shipped_at) {
            $data['shipped_at'] = now();
        }

        if (($data['status'] ?? null) === 'delivered' && ! $order?->delivered_at) {
            $data['delivered_at'] = now();
        }

        if (($data['status'] ?? null) === 'cancelled' && ! $order?->cancelled_at) {
            $data['cancelled_at'] = now();
        }

        return $data;
    }

    private function statusOptions(): array
    {
        return [
            ['label' => 'Pending', 'value' => 'pending', 'severity' => 'warn'],
            ['label' => 'Processing', 'value' => 'processing', 'severity' => 'info'],
            ['label' => 'Shipped', 'value' => 'shipped', 'severity' => 'secondary'],
            ['label' => 'Delivered', 'value' => 'delivered', 'severity' => 'success'],
            ['label' => 'Cancelled', 'value' => 'cancelled', 'severity' => 'danger'],
            ['label' => 'Returned', 'value' => 'returned', 'severity' => 'contrast'],
        ];
    }

    private function paymentStatusOptions(): array
    {
        return [
            ['label' => 'Unpaid', 'value' => 'unpaid', 'severity' => 'warn'],
            ['label' => 'Paid', 'value' => 'paid', 'severity' => 'success'],
            ['label' => 'Failed', 'value' => 'failed', 'severity' => 'danger'],
            ['label' => 'Refunded', 'value' => 'refunded', 'severity' => 'info'],
        ];
    }

    private function formatOrderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer' => $order->customer,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total' => $order->total,
            'items_count' => $order->items_count ?? 0,
            'created_at' => $order->created_at?->toDateTimeString(),
        ];
    }

    private function formatOrder(Order $order): array
    {
        return [
            ...$this->formatOrderSummary($order),
            'customer_id' => $order->customer_id,
            'address_id' => $order->address_id,
            'address' => $order->address,
            'subtotal' => $order->subtotal,
            'discount_total' => $order->discount_total,
            'shipping_cost' => $order->shipping_cost,
            'tax_total' => $order->tax_total,
            'customer_note' => $order->customer_note,
            'admin_note' => $order->admin_note,
            'paid_at' => $order->paid_at?->toDateTimeString(),
            'shipped_at' => $order->shipped_at?->toDateTimeString(),
            'delivered_at' => $order->delivered_at?->toDateTimeString(),
            'cancelled_at' => $order->cancelled_at?->toDateTimeString(),
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_price' => $item->discount_price !== null ? (float) $item->discount_price : null,
                'total_price' => (float) $item->total_price,
            ])->values(),
        ];
    }

    private function variantLabel(ProductVariant $variant): string
    {
        $attributes = $variant->attributeValues
            ->map(fn ($value) => trim(($value->attribute?->name ? "{$value->attribute->name}: " : '') . $value->value))
            ->filter()
            ->implode(' / ');

        return $attributes ?: ($variant->sku ?: "#{$variant->id}");
    }
}
