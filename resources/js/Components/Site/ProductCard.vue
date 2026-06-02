<script setup>
import { Link } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed } from 'vue';

const props = defineProps({
    product: { type: Object, required: true },
    mode: { type: String, default: 'grid' }
});

const toast = useToast();

const hasDiscount = computed(() =>
    props.product.oldPrice && Number(props.product.oldPrice) > Number(props.product.price)
);

const discountPercent = computed(() => {
    if (!hasDiscount.value) return 0;
    return Math.round((1 - Number(props.product.price) / Number(props.product.oldPrice)) * 100);
});

const onAddToCart = () => {
    toast.add({
        severity: 'info',
        summary: 'سبد خرید',
        detail: 'اتصال سبد خرید در مرحله بعد فعال می‌شود.',
        life: 1800
    });
};

const productUrl = computed(() => props.product.slug ? `/products/${props.product.slug}` : '/products');
</script>

<template>
    <article
        class="overflow-hidden rounded-xl border border-surface-200 bg-white transition hover:-translate-y-0.5 hover:shadow-md"
        :class="mode === 'list' ? 'flex flex-row gap-4 p-3' : 'flex h-full flex-col'"
    >
        <div class="relative" :class="mode === 'list' ? 'w-36 shrink-0' : ''">
            <Link :href="productUrl" class="block">
                <img
                    :src="product.image"
                    :alt="product.name"
                    class="w-full bg-surface-100 object-cover"
                    :class="mode === 'list' ? 'h-32 rounded-lg' : 'h-48'"
                />
            </Link>
            <div class="absolute left-2 top-2 flex flex-col gap-1">
                <Tag v-if="product.isNew" value="جدید" severity="info" />
                <Tag v-if="product.inStock" value="موجود" severity="success" />
                <Tag v-if="hasDiscount" :value="`${discountPercent}% تخفیف`" severity="danger" />
            </div>
            <Button
                icon="pi pi-heart"
                text
                rounded
                severity="secondary"
                class="absolute right-2 top-2 bg-white/90"
            />
        </div>

        <div class="flex min-w-0 flex-1 flex-col p-4" :class="mode === 'list' ? 'p-1' : ''">
            <p class="text-xs font-semibold text-surface-500">{{ product.brand }}</p>
            <Link :href="productUrl" class="mt-1 line-clamp-2 text-sm font-bold text-surface-900 hover:text-[#D4A017]">{{ product.name }}</Link>
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
