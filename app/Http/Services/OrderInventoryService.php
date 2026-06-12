<?php

namespace App\Http\Services;

use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OrderInventoryService
{
    public function sync(Order $order, ?int $userId = null): void
    {
        $order->loadMissing('items');

        $reducingStatuses = config('shop.orders.inventory_reducing_statuses', ['processing', 'shipped', 'delivered']);
        $returningStatuses = config('shop.orders.inventory_returning_statuses', ['cancelled', 'returned']);
        $shouldReduce = ($order->payment_status === 'paid' || in_array($order->status, $reducingStatuses, true))
            && ! in_array($order->status, $returningStatuses, true);

        if ($shouldReduce && $order->stock_reserved_at && ! $order->stock_released_at) {
            $this->confirm($order, $userId);
            return;
        }

        if ($shouldReduce && ! $order->inventory_reduced_at) {
            $this->reduce($order, $userId);
            $order->forceFill(['inventory_reduced_at' => now(), 'inventory_returned_at' => null])->save();
        }

        if (in_array($order->status, $returningStatuses, true) && $order->stock_reserved_at && ! $order->stock_released_at) {
            $this->release($order, $userId, $order->status === 'returned' ? 'return' : 'correction');
            return;
        }

        if (in_array($order->status, $returningStatuses, true) && $order->inventory_reduced_at && ! $order->inventory_returned_at) {
            $this->return($order, $userId, $order->status === 'returned' ? 'return' : 'correction');
        }
    }

    public function reserve(Order $order, ?int $userId = null): void
    {
        $order = Order::query()->lockForUpdate()->with('items')->findOrFail($order->id);

        if ($order->stock_reserved_at && ! $order->stock_released_at) {
            return;
        }

        if ($order->stock_released_at) {
            throw ValidationException::withMessages(['cart' => 'موجودی این سفارش قبلاً آزاد شده و قابل رزرو دوباره نیست.']);
        }

        foreach ($order->items as $item) {
            [$stockable, $productId, $variantId] = $this->stockableForItem($item, true);
            $previous = (int) ($stockable->stock ?? 0);

            if ($previous < (int) $item->quantity) {
                throw ValidationException::withMessages(['cart' => "موجودی {$item->product_name} کافی نیست."]);
            }

            $new = $previous - (int) $item->quantity;
            $stockable->update(['stock' => $new]);
            $this->log($productId, $variantId, 'order', (int) $item->quantity, $previous, $new, "Reserved Order {$order->order_number}", $userId);
        }

        $order->forceFill([
            'stock_reserved_at' => now(),
            'stock_released_at' => null,
        ])->save();
    }

    public function release(Order $order, ?int $userId = null, string $type = 'return'): void
    {
        $order = Order::query()->lockForUpdate()->with('items')->findOrFail($order->id);

        if (! $order->stock_reserved_at || $order->stock_released_at) {
            return;
        }

        if (! $this->canRelease($order)) {
            return;
        }

        $logType = in_array($type, ['return', 'correction'], true) ? $type : 'return';

        foreach ($order->items as $item) {
            [$stockable, $productId, $variantId] = $this->stockableForItem($item, false);
            $previous = (int) ($stockable->stock ?? 0);
            $new = $previous + (int) $item->quantity;

            $stockable->update(['stock' => $new]);
            $this->log($productId, $variantId, $logType, (int) $item->quantity, $previous, $new, "Released Order {$order->order_number}", $userId);
        }

        $order->forceFill(['stock_released_at' => now()])->save();
    }

    public function confirm(Order $order, ?int $userId = null): void
    {
        $order = Order::query()->lockForUpdate()->findOrFail($order->id);

        if (! $order->stock_reserved_at || $order->stock_released_at) {
            return;
        }

        $order->forceFill([
            'payment_status' => 'paid',
            'status' => 'processing',
            'paid_at' => $order->paid_at ?? now(),
            'inventory_reduced_at' => $order->inventory_reduced_at ?? $order->stock_reserved_at,
            'inventory_returned_at' => null,
        ])->save();
    }

    public function return(Order $order, ?int $userId = null, string $type = 'return'): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            [$stockable, $productId, $variantId] = $this->stockableForItem($item, false);
            $previous = (int) ($stockable->stock ?? 0);
            $new = $previous + $item->quantity;

            $stockable->update(['stock' => $new]);
            $this->log($productId, $variantId, $type, $item->quantity, $previous, $new, "Order {$order->order_number}", $userId);
        }

        $order->forceFill(['inventory_returned_at' => now()])->save();
    }

    private function reduce(Order $order, ?int $userId): void
    {
        foreach ($order->items as $item) {
            [$stockable, $productId, $variantId] = $this->stockableForItem($item, true);
            $previous = (int) ($stockable->stock ?? 0);

            if ($previous < (int) $item->quantity) {
                throw ValidationException::withMessages(['items' => "Not enough stock for {$item->product_name}."]);
            }

            $new = $previous - (int) $item->quantity;
            $stockable->update(['stock' => $new]);
            $this->log($productId, $variantId, 'order', $item->quantity, $previous, $new, "Order {$order->order_number}", $userId);
        }
    }

    private function stockableForItem(OrderItem $item, bool $validatePurchasable): array
    {
        $product = Product::query()->lockForUpdate()->find($item->product_id);

        if (! $product || ($validatePurchasable && $product->status !== 'active')) {
            throw ValidationException::withMessages([
                'cart' => 'یکی از محصولات سبد خرید دیگر قابل سفارش نیست.',
            ]);
        }

        if ($item->product_variant_id) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->find($item->product_variant_id);

            if (! $variant || ($validatePurchasable && ! $this->variantIsPurchasable($variant))) {
                throw ValidationException::withMessages([
                    'cart' => 'یکی از واریانت‌های سبد خرید دیگر قابل سفارش نیست.',
                ]);
            }

            return [$variant, null, $variant->id];
        }

        return [$product, $product->id, null];
    }

    private function variantIsPurchasable(ProductVariant $variant): bool
    {
        if (Schema::hasColumn('product_variants', 'is_active') && ! (bool) $variant->is_active) {
            return false;
        }

        if (Schema::hasColumn('product_variants', 'status') && ! in_array($variant->status, ['active', 'published'], true)) {
            return false;
        }

        return true;
    }

    private function canRelease(Order $order): bool
    {
        if ($order->payment_status === 'paid') {
            return false;
        }

        return in_array($order->status, ['pending', 'cancelled', 'returned'], true)
            && in_array($order->payment_status, ['unpaid', 'failed', 'refunded'], true);
    }

    private function log(?int $productId, ?int $variantId, string $type, int $quantity, int $previous, int $new, string $note, ?int $userId): void
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
}
