<script setup>
defineProps({
    question: {
        type: String,
        default: 'Question placeholder'
    },
    answer: {
        type: String,
        default: 'Answer placeholder'
    }
});
</script>

<template>
    <article class="site-card p-5">
        <h2 class="font-black text-[#1A1A1A]">{{ question }}</h2>
        <p class="mt-2 text-sm leading-7 text-[#666666]">{{ answer }}</p>
    </article>
</template>
