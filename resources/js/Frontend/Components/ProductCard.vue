<script setup>
defineProps({
    product: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
    <article class="product-premium-card group">
        <div class="product-media-wrap">
            <div class="product-badge">ویژه</div>
            <div class="product-media">
                <span class="product-icon">{{ product.icon || 'MP' }}</span>
            </div>
        </div>
        <div class="p-5">
            <p class="text-xs font-bold tracking-[0.12em] text-[#D4A017]">{{ product.category || 'MotoPart' }}</p>
            <h3 class="mt-2 line-clamp-2 text-lg font-black text-[#1A1A1A]">{{ product.name || 'قطعه MotoPart' }}</h3>
            <p class="mt-2 text-sm leading-6 text-[#666666]">{{ product.description || 'قطعه منتخب فروشگاه MotoPart' }}</p>
            <div class="mt-4 flex items-center justify-between">
                <span class="text-xl font-black text-[#111111]">{{ product.price || 'تماس بگیرید' }}</span>
                <span class="rounded-full border border-[#D4A017] px-3 py-1 text-[11px] font-bold text-[#111111]">اصل</span>
            </div>
            <button class="site-btn-dark mt-5 w-full">افزودن به سبد خرید</button>
        </div>
    </article>
</template>
