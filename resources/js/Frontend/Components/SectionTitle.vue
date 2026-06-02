<script setup>
defineProps({
    title: {
        type: String,
        default: ''
    },
    subtitle: {
        type: String,
        default: ''
    },
    tone: {
        type: String,
        default: 'light'
    }
});
</script>

<template>
    <section>
        <p class="site-gold-kicker">MotoPart</p>
        <h2 :class="['mt-2 text-3xl font-black leading-tight md:text-4xl', tone === 'dark' ? 'site-section-title-dark' : 'site-section-title']">{{ title }}</h2>
        <p v-if="subtitle" :class="['mt-3 max-w-2xl text-sm leading-7', tone === 'dark' ? 'site-section-subtitle-dark' : 'site-section-subtitle']">{{ subtitle }}</p>
    </section>
</template>
