<?php

namespace App\Http\Services;

use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
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

        if ($shouldReduce && ! $order->inventory_reduced_at) {
            $this->reduce($order, $userId);
            $order->forceFill(['inventory_reduced_at' => now(), 'inventory_returned_at' => null])->save();
        }

        if (in_array($order->status, $returningStatuses, true) && $order->inventory_reduced_at && ! $order->inventory_returned_at) {
            $this->return($order, $userId, $order->status === 'returned' ? 'return' : 'correction');
        }
    }

    public function return(Order $order, ?int $userId = null, string $type = 'return'): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            [$stockable, $productId, $variantId] = $this->stockableForItem($item);
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
            [$stockable, $productId, $variantId] = $this->stockableForItem($item);
            $previous = (int) ($stockable->stock ?? 0);

            if ($previous - $item->quantity < 0) {
                throw ValidationException::withMessages(['items' => "Not enough stock for {$item->product_name}."]);
            }

            $new = $previous - $item->quantity;
            $stockable->update(['stock' => $new]);
            $this->log($productId, $variantId, 'order', $item->quantity, $previous, $new, "Order {$order->order_number}", $userId);
        }
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
