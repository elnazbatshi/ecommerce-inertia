<?php

namespace App\Http\Services;

use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private readonly OrderInventoryService $inventory)
    {
    }

    public function paginated(Request $request)
    {
        return Order::query()
            ->with('customer:id,name,phone')
            ->withCount('items')
            ->search($request->string('search')->toString())
            ->filter($request->only(['status', 'payment_status', 'date_from', 'date_to']))
            ->latest()
            ->paginate(Pagination::perPage($request))
            ->withQueryString()
            ->through(fn (Order $order) => OrderResource::make($order)->resolve());
    }

    public function customerOptions()
    {
        return Customer::query()
            ->with(['addresses.province:id,name', 'addresses.city:id,name'])
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
                    'province_id' => $address->province_id,
                    'city_id' => $address->city_id,
                    'province_name' => $address->province?->name ?? $address->province,
                    'city_name' => $address->city?->name ?? $address->city,
                    'province' => $address->province,
                    'city' => $address->city,
                    'address' => $address->address,
                    'is_default' => $address->is_default,
                    'label' => trim(implode(' | ', array_filter([
                        $address->title ?: 'آدرس',
                        trim(($address->province?->name ?? $address->province ?? '-') . ' / ' . ($address->city?->name ?? $address->city ?? '-')),
                        $address->address,
                    ]))),
                ])->values(),
            ]);
    }

    public function productOptions()
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

    public function create(array $data, ?int $userId): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $payload = $this->payload($data);
            $order = Order::create([
                ...$payload['order'],
                'order_number' => $this->generateNumber(),
            ]);

            $this->syncItems($order, $payload['items']);
            $this->inventory->sync($order->refresh(), $userId);

            return $order;
        });
    }

    public function update(Order $order, array $data, ?int $userId): void
    {
        DB::transaction(function () use ($order, $data, $userId) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->inventory_reduced_at && ! $order->inventory_returned_at) {
                $this->inventory->return($order, $userId, 'correction');
                $order->forceFill(['inventory_reduced_at' => null])->save();
            }

            $payload = $this->payload($data);
            $order->update($payload['order']);
            $order->items()->delete();
            $this->syncItems($order, $payload['items']);
            $this->inventory->sync($order->refresh(), $userId);
        });
    }

    public function delete(Order $order, ?int $userId): void
    {
        DB::transaction(function () use ($order, $userId) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->inventory_reduced_at && ! $order->inventory_returned_at) {
                $this->inventory->return($order, $userId, 'return');
            }

            $order->delete();
        });
    }

    public function updateStatus(Order $order, string $status, ?int $userId): void
    {
        DB::transaction(function () use ($order, $status, $userId) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $order->update($this->statusTimestamps($order, ['status' => $status]));
            $this->inventory->sync($order->refresh(), $userId);
        });
    }

    public function updatePaymentStatus(Order $order, string $status, ?int $userId): void
    {
        DB::transaction(function () use ($order, $status, $userId) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $order->update($this->statusTimestamps($order, ['payment_status' => $status]));
            $this->inventory->sync($order->refresh(), $userId);
        });
    }

    public function payload(array $data): array
    {
        $shippingSnapshot = $this->buildShippingSnapshot($data);

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

        $subtotal = $items->sum(fn (array $item) => $item['unit_price'] * $item['quantity']);
        $itemDiscount = $items->sum(fn (array $item) => max(0, ($item['unit_price'] - ($item['discount_price'] ?? $item['unit_price'])) * $item['quantity']));
        $manualDiscount = (float) ($data['discount_total'] ?? 0);
        $shipping = (float) ($data['shipping_cost'] ?? 0);
        $tax = (float) ($data['tax_total'] ?? 0);
        $discountTotal = $itemDiscount + $manualDiscount;

        return [
            'order' => $this->statusTimestamps(null, [
                'customer_id' => $data['customer_id'],
                'address_id' => $data['address_id'] ?? null,
                ...$shippingSnapshot,
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_cost' => $shipping,
                'tax_total' => $tax,
                'total' => max(0, $subtotal - $discountTotal + $shipping + $tax),
                'customer_note' => $data['customer_note'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
            ]),
            'items' => $items,
        ];
    }

    private function buildShippingSnapshot(array $data): array
    {
        if (blank($data['address_id'] ?? null)) {
            return [
                'shipping_receiver_name' => null,
                'shipping_receiver_phone' => null,
                'shipping_province_name' => null,
                'shipping_city_name' => null,
                'shipping_address' => null,
                'shipping_postal_code' => null,
                'shipping_plaque' => null,
                'shipping_unit' => null,
            ];
        }

        $address = Address::query()
            ->with(['province:id,name', 'city:id,name'])
            ->findOrFail($data['address_id']);

        return [
            'shipping_receiver_name' => $address->receiver_name,
            'shipping_receiver_phone' => $address->receiver_phone,
            'shipping_province_name' => $address->province?->name ?? $address->province,
            'shipping_city_name' => $address->city?->name ?? $address->city,
            'shipping_address' => $address->address,
            'shipping_postal_code' => $address->postal_code,
            'shipping_plaque' => $address->plaque,
            'shipping_unit' => $address->unit,
        ];
    }

    public function variantLabel(ProductVariant $variant): string
    {
        $attributes = $variant->attributeValues
            ->map(fn ($value) => trim(($value->attribute?->name ? "{$value->attribute->name}: " : '') . $value->value))
            ->filter()
            ->implode(' / ');

        return $attributes ?: ($variant->sku ?: "#{$variant->id}");
    }

    private function syncItems(Order $order, Collection $items): void
    {
        foreach ($items as $item) {
            $order->items()->create($item);
        }
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

    private function generateNumber(): string
    {
        $prefix = config('shop.orders.number_prefix', 'ORD') . '-' . now()->format('Ymd') . '-';
        $count = Order::query()->where('order_number', 'like', "{$prefix}%")->lockForUpdate()->count() + 1;

        do {
            $number = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
