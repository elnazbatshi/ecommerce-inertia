<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    product: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <article class="product-premium-card group">
        <div class="product-media-wrap">
            <div class="product-badge">ویژه</div>
            <Link :href="product.slug ? `/products/${product.slug}` : '/products'" class="product-media overflow-hidden">
                <img
                    v-if="product.image"
                    :src="product.image"
                    :alt="product.name || 'محصول MotoPart'"
                    class="h-full w-full rounded-[0.85rem] object-cover transition duration-300 group-hover:scale-105"
                    loading="lazy"
                />
                <span v-else class="product-icon">{{ product.icon || 'MP' }}</span>
            </Link>
        </div>
        <div class="p-5">
            <p class="text-xs font-bold tracking-[0.12em] text-[#D4A017]">{{ product.category || 'MotoPart' }}</p>
            <Link :href="product.slug ? `/products/${product.slug}` : '/products'">
                <h3 class="mt-2 line-clamp-2 text-lg font-black text-[#1A1A1A] transition hover:text-[#D4A017]">
                    {{ product.name || 'قطعه MotoPart' }}
                </h3>
            </Link>
            <p class="mt-2 text-sm leading-6 text-[#666666]">{{ product.description || 'قطعه منتخب فروشگاه MotoPart' }}</p>
            <div class="mt-4 flex items-center justify-between">
                <span class="text-xl font-black text-[#111111]">{{ product.price || 'تماس بگیرید' }}</span>
                <span class="rounded-full border border-[#D4A017] px-3 py-1 text-[11px] font-bold text-[#111111]">اصل</span>
            </div>
            <button class="site-btn-dark mt-5 w-full">افزودن به سبد خرید</button>
        </div>
    </article>
</template>
