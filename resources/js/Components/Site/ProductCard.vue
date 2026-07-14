<script setup>
import { Link } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';
import { useCart } from '@/Composables/useCart';

const props = defineProps({
    product: { type: Object, required: true },
    mode: { type: String, default: 'grid' }
});

const emit = defineEmits(['wishlist-updated']);

const toast = useToast();
const { addItem } = useCart();
const imageLoaded = ref(false);
const imageFailed = ref(false);
const isWishlisted = ref(Boolean(props.product.is_wishlisted));
const isWishlistBusy = ref(false);

const hasDiscount = computed(() =>
    props.product.oldPrice && Number(props.product.oldPrice) > Number(props.product.price)
);

const discountPercent = computed(() => {
    if (!hasDiscount.value) return 0;
    return Math.round((1 - Number(props.product.price) / Number(props.product.oldPrice)) * 100);
});

const productUrl = computed(() => props.product.url || (props.product.slug ? `/products/${props.product.slug}` : null));
const imageSrc = computed(() => {
    if (imageFailed.value || !props.product.image) {
        return '/images/product-placeholder.svg';
    }

    return props.product.image;
});

watch(
    () => props.product.image,
    () => {
        imageLoaded.value = false;
        imageFailed.value = false;
    },
    { immediate: true }
);

watch(
    () => props.product.is_wishlisted,
    (value) => {
        isWishlisted.value = Boolean(value);
    }
);

const handleImageLoad = () => {
    imageLoaded.value = true;
};

const handleImageError = () => {
    imageFailed.value = true;
    imageLoaded.value = true;
};

const requestCustomerAuth = () => {
    window.dispatchEvent(new CustomEvent('motopart:open-customer-auth'));
};

const toggleWishlist = async () => {
    if (!props.product.slug || isWishlistBusy.value) {
        return;
    }

    const previousValue = isWishlisted.value;
    isWishlistBusy.value = true;
    isWishlisted.value = !previousValue;

    try {
        const { data } = await window.axios.post(`/wishlist/products/${props.product.slug}/toggle`);
        isWishlisted.value = Boolean(data.is_wishlisted);
        props.product.is_wishlisted = isWishlisted.value;
        emit('wishlist-updated', {
            product: props.product,
            is_wishlisted: isWishlisted.value,
        });

        toast.add({
            severity: isWishlisted.value ? 'success' : 'info',
            summary: 'علاقه‌مندی‌ها',
            detail: data.message,
            life: 1600
        });
    } catch (error) {
        isWishlisted.value = previousValue;

        if (error.response?.status === 401) {
            requestCustomerAuth();
            return;
        }

        toast.add({
            severity: 'error',
            summary: 'علاقه‌مندی‌ها',
            detail: 'امکان بروزرسانی علاقه‌مندی‌ها وجود ندارد.',
            life: 2200
        });
    } finally {
        isWishlistBusy.value = false;
    }
};

const onAddToCart = () => {
    addItem({
        id: props.product.id,
        slug: props.product.slug,
        name: props.product.name,
        brand: props.product.brand,
        sku: props.product.sku,
        image: props.product.image,
        price: props.product.price,
        old_price: props.product.oldPrice,
        stock: props.product.stock ?? (props.product.inStock ? 999 : 0),
    });

    toast.add({
        severity: 'success',
        summary: 'سبد خرید',
        detail: 'محصول به سبد خرید اضافه شد.',
        life: 1800
    });
};
</script>

<template>
    <article
        class="overflow-hidden rounded-xl border border-surface-200 bg-white transition hover:-translate-y-0.5 hover:shadow-md"
        :class="mode === 'list' ? 'flex flex-row gap-4 p-3' : 'flex h-full flex-col'"
    >
        <div class="relative" :class="mode === 'list' ? 'w-36 shrink-0' : ''">
            <Link v-if="productUrl" :href="productUrl" class="block">
                <div
                    v-if="!imageLoaded"
                    class="absolute inset-0 z-[1] animate-pulse bg-gradient-to-br from-surface-100 via-surface-50 to-surface-200"
                    :class="mode === 'list' ? 'rounded-lg' : ''"
                >
                    <div class="flex h-full w-full items-center justify-center text-surface-300">
                        <i class="pi pi-image text-2xl" />
                    </div>
                </div>
                <img
                    :src="imageSrc"
                    :alt="product.name"
                    class="w-full bg-surface-100 object-cover transition-opacity duration-300"
                    :class="[
                        mode === 'list' ? 'h-32 rounded-lg' : 'h-48',
                        imageLoaded ? 'opacity-100' : 'opacity-0'
                    ]"
                    loading="lazy"
                    @load="handleImageLoad"
                    @error="handleImageError"
                />
            </Link>
            <template v-else>
                <div
                    v-if="!imageLoaded"
                    class="absolute inset-0 z-[1] animate-pulse bg-gradient-to-br from-surface-100 via-surface-50 to-surface-200"
                    :class="mode === 'list' ? 'h-32 rounded-lg' : 'h-48'"
                >
                    <div class="flex h-full w-full items-center justify-center text-surface-300">
                        <i class="pi pi-image text-2xl" />
                    </div>
                </div>
                <img
                    :src="imageSrc"
                    :alt="product.name"
                    class="w-full bg-surface-100 object-cover transition-opacity duration-300"
                    :class="[
                        mode === 'list' ? 'h-32 rounded-lg' : 'h-48',
                        imageLoaded ? 'opacity-100' : 'opacity-0'
                    ]"
                    loading="lazy"
                    @load="handleImageLoad"
                    @error="handleImageError"
                />
            </template>
            <div class="absolute left-2 top-2 flex flex-col gap-1">
                <Tag v-if="product.isNew" value="جدید" severity="info" />
                <Tag v-if="product.inStock" value="موجود" severity="success" />
                <Tag v-if="hasDiscount" :value="`${discountPercent}% تخفیف`" severity="danger" />
            </div>
            <Button
                :icon="isWishlisted ? 'pi pi-heart-fill' : 'pi pi-heart'"
                text
                rounded
                :severity="isWishlisted ? 'danger' : 'secondary'"
                :loading="isWishlistBusy"
                class="absolute right-2 top-2 z-[2] bg-white/90"
                :aria-label="isWishlisted ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها'"
                @click.stop.prevent="toggleWishlist"
            />
        </div>

        <div class="flex min-w-0 flex-1 flex-col p-4" :class="mode === 'list' ? 'p-1' : ''">
            <p class="text-xs font-semibold text-surface-500">{{ product.brand }}</p>
            <Link v-if="productUrl" :href="productUrl" class="mt-1 line-clamp-2 text-sm font-bold text-surface-900 hover:text-[#D4A017]">{{ product.name }}</Link>
            <h3 v-else class="mt-1 line-clamp-2 text-sm font-bold text-surface-900">{{ product.name }}</h3>
            <p class="mt-2 line-clamp-2 text-xs text-surface-600">{{ product.feature }}</p>

            <div class="mt-auto pt-4">
                <div class="mb-2 flex items-center gap-2">
                    <span class="text-base font-black text-surface-900">{{ Number(product.price).toLocaleString('fa-IR') }} تومان</span>
                    <span v-if="hasDiscount" class="text-xs text-surface-400 line-through">{{ Number(product.oldPrice).toLocaleString('fa-IR') }}</span>
                </div>
                <Button
                    label="افزودن به سبد خرید"
                    icon="pi pi-shopping-cart"
                    size="small"
                    class="w-full"
                    :disabled="!product.inStock"
                    @click.stop="onAddToCart"
                />
            </div>
        </div>
    </article>
</template>
