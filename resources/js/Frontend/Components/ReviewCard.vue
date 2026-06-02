<script setup>
defineProps({
    review: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
    <article class="site-card p-5">
        <div class="flex items-center justify-between gap-4">
            <h3 class="font-black text-[#1A1A1A]">{{ review.author || 'راننده MotoPart' }}</h3>
            <span class="text-sm text-[#D4A017]">{{ review.vehicle || 'خودرو' }}</span>
        </div>
        <p class="mt-4 text-sm leading-7 text-[#666666]">{{ review.body || 'تجربه نصب و خرید از MotoPart' }}</p>
    </article>
</template>
