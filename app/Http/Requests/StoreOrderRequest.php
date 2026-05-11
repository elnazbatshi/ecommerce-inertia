<?php

namespace App\Http\Requests;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'status' => ['required', Rule::in(Order::STATUSES)],
            'payment_status' => ['required', Rule::in(Order::PAYMENT_STATUSES)],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'customer_note' => ['nullable', 'string'],
            'admin_note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('address_id')) {
                $belongs = Address::query()
                    ->whereKey($this->input('address_id'))
                    ->where('customer_id', $this->input('customer_id'))
                    ->exists();

                if (! $belongs) {
                    $validator->errors()->add('address_id', 'Selected address does not belong to this customer.');
                }
            }

            foreach ($this->input('items', []) as $index => $item) {
                $product = Product::query()->find($item['product_id'] ?? null);

                if (! $product) {
                    continue;
                }

                if ($product->type === 'variable') {
                    if (blank($item['product_variant_id'] ?? null)) {
                        $validator->errors()->add("items.{$index}.product_variant_id", 'Variant is required for variable products.');
                        continue;
                    }

                    $variant = ProductVariant::query()
                        ->whereKey($item['product_variant_id'])
                        ->where('product_id', $product->id)
                        ->first();

                    if (! $variant) {
                        $validator->errors()->add("items.{$index}.product_variant_id", 'Selected variant does not belong to this product.');
                        continue;
                    }

                    $availableStock = (int) $variant->stock + $this->reservedQuantityFor(null, $variant->id);

                    if ((int) $item['quantity'] > $availableStock) {
                        $validator->errors()->add("items.{$index}.quantity", 'Quantity is greater than variant stock.');
                    }
                }

                if ($product->type === 'simple') {
                    if (filled($item['product_variant_id'] ?? null)) {
                        $validator->errors()->add("items.{$index}.product_variant_id", 'Simple products cannot have a variant.');
                    }

                    $availableStock = (int) ($product->stock ?? 0) + $this->reservedQuantityFor($product->id, null);

                    if ((int) $item['quantity'] > $availableStock) {
                        $validator->errors()->add("items.{$index}.quantity", 'Quantity is greater than product stock.');
                    }
                }
            }
        });
    }

    private function reservedQuantityFor(?int $productId, ?int $variantId): int
    {
        $order = $this->route('order');

        if (! $order || ! $order->inventory_reduced_at || $order->inventory_returned_at) {
            return 0;
        }

        return (int) $order->items()
            ->when($variantId, fn ($query) => $query->where('product_variant_id', $variantId))
            ->when(! $variantId, fn ($query) => $query->where('product_id', $productId)->whereNull('product_variant_id'))
            ->sum('quantity');
    }
}
