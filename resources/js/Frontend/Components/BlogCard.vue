<script setup>
defineProps({
    post: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
    <article class="site-card p-5">
        <p class="text-xs font-black text-[#D4A017]">MotoPart Journal</p>
        <h2 class="mt-2 text-lg font-black text-[#1A1A1A]">{{ post.title || 'Blog placeholder' }}</h2>
    </article>
</template>
