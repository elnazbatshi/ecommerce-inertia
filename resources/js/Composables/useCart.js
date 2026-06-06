import { computed, ref } from 'vue';

const STORAGE_KEY = 'motopart_cart';
const items = ref([]);
let initialized = false;

const normalizeNumber = (value, fallback = 0) => {
    const number = Number(value);

    return Number.isFinite(number) ? number : fallback;
};

const loadCart = () => {
    if (initialized || typeof window === 'undefined') return;

    try {
        const stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '[]');
        items.value = Array.isArray(stored) ? stored : [];
    } catch {
        items.value = [];
    }

    initialized = true;
};

const persistCart = () => {
    if (typeof window === 'undefined') return;

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items.value));
    window.dispatchEvent(new CustomEvent('motopart-cart-updated'));
};

const cartKey = (productId, variantId = null) => `${productId}:${variantId || 'default'}`;

export function useCart() {
    loadCart();

    const count = computed(() => items.value.reduce((sum, item) => sum + normalizeNumber(item.quantity), 0));
    const subtotal = computed(() => items.value.reduce((sum, item) => sum + normalizeNumber(item.price) * normalizeNumber(item.quantity), 0));
    const hasItems = computed(() => items.value.length > 0);

    const addItem = (payload, quantity = 1) => {
        const productId = payload.product_id ?? payload.id;
        if (!productId) return;

        const variantId = payload.variant_id ?? null;
        const key = cartKey(productId, variantId);
        const requestedQuantity = Math.max(1, normalizeNumber(quantity, 1));
        const maxStock = Math.max(0, normalizeNumber(payload.stock, 999));
        const existing = items.value.find((item) => item.key === key);

        if (existing) {
            existing.quantity = Math.min(maxStock || requestedQuantity, normalizeNumber(existing.quantity) + requestedQuantity);
        } else {
            items.value.push({
                key,
                product_id: productId,
                variant_id: variantId,
                slug: payload.slug,
                name: payload.name,
                variant_label: payload.variant_label || null,
                brand: payload.brand || null,
                sku: payload.sku || null,
                image: payload.image || null,
                price: normalizeNumber(payload.price),
                old_price: payload.old_price ? normalizeNumber(payload.old_price) : null,
                stock: maxStock,
                quantity: Math.min(maxStock || requestedQuantity, requestedQuantity),
            });
        }

        persistCart();
    };

    const updateQuantity = (key, quantity) => {
        const item = items.value.find((cartItem) => cartItem.key === key);
        if (!item) return;

        const nextQuantity = Math.max(1, normalizeNumber(quantity, 1));
        item.quantity = Math.min(item.stock || nextQuantity, nextQuantity);
        persistCart();
    };

    const removeItem = (key) => {
        items.value = items.value.filter((item) => item.key !== key);
        persistCart();
    };

    const clearCart = () => {
        items.value = [];
        persistCart();
    };

    return {
        items,
        count,
        subtotal,
        hasItems,
        addItem,
        updateQuantity,
        removeItem,
        clearCart,
    };
}
