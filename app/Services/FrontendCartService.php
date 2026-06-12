<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class FrontendCartService
{
    public function sync(Customer $customer, array $items, ?string $sessionId = null): Cart
    {
        return DB::transaction(function () use ($customer, $items, $sessionId) {
            $cart = $this->resolveActiveCart($customer, $sessionId);
            $incomingItems = $this->normalizeIncomingItems($items);

            foreach ($incomingItems as $item) {
                $product = Product::query()
                    ->with(['variants.attributeValues.attribute'])
                    ->find($item['product_id']);

                if (! $this->isPurchasableProduct($product)) {
                    continue;
                }

                $variant = $item['product_variant_id']
                    ? $product->variants->firstWhere('id', $item['product_variant_id'])
                    : null;

                if ($item['product_variant_id'] && ! $variant) {
                    continue;
                }

                $availableStock = $this->availableStock($product, $variant);
                if ($availableStock <= 0) {
                    continue;
                }

                $cartItem = $cart->items()
                    ->where('product_id', $product->id)
                    ->where('product_variant_id', $variant?->id)
                    ->first();

                $nextQuantity = $item['quantity'];
                if ($cartItem) {
                    $nextQuantity += (int) $cartItem->quantity;
                }

                $cartItemData = [
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => min($availableStock, $nextQuantity),
                ];

                if ($cartItem) {
                    $cartItem->update(['quantity' => $cartItemData['quantity']]);
                } else {
                    $cart->items()->create($cartItemData);
                }
            }

            return $this->loadCart($cart->fresh());
        });
    }

    public function activeCartFor(Customer $customer): ?Cart
    {
        return DB::transaction(function () use ($customer) {
            $carts = Cart::query()
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            if ($carts->isEmpty()) {
                return null;
            }

            /** @var Cart $cart */
            $cart = $carts->shift();

            if ($carts->isNotEmpty()) {
                $this->mergeDuplicateCarts($cart, $carts);
            }

            return $this->loadCart($cart->fresh());
        });
    }

    public function subtotal(Cart $cart): float
    {
        return $cart->items->sum(fn ($item) => $this->itemUnitPrice($item) * $item->quantity);
    }

    public function itemUnitPrice($item): float
    {
        $variant = $item->productVariant;
        $product = $item->product;

        return (float) ($variant?->discount_price ?: $variant?->price ?: $product?->discount_price ?: $product?->price ?: 0);
    }

    public function serialize(Cart $cart): array
    {
        $items = $cart->items
            ->filter(fn ($item) => $item->product)
            ->map(function ($item) {
                $unitPrice = $this->itemUnitPrice($item);

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'sku' => $item->productVariant?->sku ?: $item->product->sku,
                    'variant_name' => $this->variantName($item->productVariant),
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $item->quantity,
                ];
            })
            ->values();

        return [
            'id' => $cart->id,
            'items' => $items,
            'subtotal' => $items->sum('total_price'),
        ];
    }

    public function productAvailability(Product $product, ?ProductVariant $variant = null): int
    {
        return $this->availableStock($product, $variant);
    }

    public function variantLabel(?ProductVariant $variant): ?string
    {
        return $this->variantName($variant);
    }

    private function resolveActiveCart(Customer $customer, ?string $sessionId = null): Cart
    {
        $cart = $this->activeCartFor($customer);

        if ($cart) {
            if ($sessionId && ! $cart->session_id) {
                $cart->forceFill(['session_id' => $sessionId])->save();
            }

            return $cart;
        }

        return Cart::create([
            'customer_id' => $customer->id,
            'session_id' => $sessionId,
            'status' => 'active',
        ]);
    }

    /**
     * @param  Collection<int, Cart>  $duplicateCarts
     */
    private function mergeDuplicateCarts(Cart $primaryCart, Collection $duplicateCarts): void
    {
        foreach ($duplicateCarts as $duplicateCart) {
            $duplicateCart->loadMissing(['items.product', 'items.productVariant.attributeValues.attribute']);

            foreach ($duplicateCart->items as $item) {
                $product = $item->product;

                if (! $this->isPurchasableProduct($product)) {
                    continue;
                }

                $variant = $item->productVariant;
                $availableStock = $this->availableStock($product, $variant);
                if ($availableStock <= 0) {
                    continue;
                }

                $targetItem = $primaryCart->items()
                    ->where('product_id', $product->id)
                    ->where('product_variant_id', $variant?->id)
                    ->first();

                $nextQuantity = (int) $item->quantity + (int) ($targetItem->quantity ?? 0);

                if ($targetItem) {
                    $targetItem->update(['quantity' => min($availableStock, $nextQuantity)]);
                } else {
                    $primaryCart->items()->create([
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'quantity' => min($availableStock, (int) $item->quantity),
                    ]);
                }
            }

            $duplicateCart->items()->delete();
            $duplicateCart->update(['status' => 'abandoned']);
        }
    }

    private function normalizeIncomingItems(array $items): Collection
    {
        return collect($items)
            ->map(function ($item) {
                $productId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
                $variantId = isset($item['product_variant_id']) ? (int) $item['product_variant_id'] : (isset($item['variant_id']) ? (int) $item['variant_id'] : null);

                return [
                    'product_id' => $productId ?: null,
                    'product_variant_id' => $variantId ?: null,
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                ];
            })
            ->filter(fn ($item) => ! empty($item['product_id']))
            ->groupBy(fn ($item) => $this->cartKey($item['product_id'], $item['product_variant_id']))
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'product_id' => $first['product_id'],
                    'product_variant_id' => $first['product_variant_id'],
                    'quantity' => $group->sum('quantity'),
                ];
            })
            ->values();
    }

    private function loadCart(Cart $cart): Cart
    {
        return $cart->load(['items.product', 'items.productVariant.attributeValues.attribute']);
    }

    private function isPurchasableProduct(?Product $product): bool
    {
        return (bool) $product && $product->status === 'active';
    }

    private function availableStock(Product $product, ?ProductVariant $variant = null): int
    {
        $stock = $variant?->stock ?? $product->stock;

        return max(0, (int) ($stock ?? 0));
    }

    private function cartKey(int $productId, ?int $variantId = null): string
    {
        return "{$productId}:" . ($variantId ?: 'default');
    }

    private function variantName(?ProductVariant $variant): ?string
    {
        if (! $variant) {
            return null;
        }

        $labels = $variant->attributeValues
            ->map(fn ($value) => trim(($value->attribute?->name ? "{$value->attribute->name}: " : '') . $value->value))
            ->filter();

        return $labels->isNotEmpty() ? $labels->implode(' / ') : $variant->sku;
    }
}
