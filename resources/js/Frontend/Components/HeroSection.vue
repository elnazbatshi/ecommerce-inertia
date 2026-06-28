<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const slides = ref([]);
const activeIndex = ref(0);
const timer = ref(null);
const loading = ref(true);

const activeSlide = computed(() => slides.value[activeIndex.value] ?? null);
const hasMultiple = computed(() => slides.value.length > 1);

const normalizeColor = (value, fallback) => {
    if (!value) {
        return fallback;
    }

    return String(value).startsWith('#') ? value : `#${value}`;
};

const textColor = computed(() => normalizeColor(activeSlide.value?.colors?.text, '#ffffff'));
const accentColor = computed(() => normalizeColor(activeSlide.value?.colors?.accent, '#D4A017'));
const buttonColor = computed(() => normalizeColor(activeSlide.value?.colors?.button, '#D4A017'));
const isCentered = computed(() => activeSlide.value?.layout === 'content_center');
const contentFirst = computed(() => activeSlide.value?.layout === 'image_right_content_left');

const loadSlides = async () => {
    try {
        const { data } = await axios.get('/api/frontend/hero-sliders', {
            params: { placement: 'hero' },
        });

        slides.value = Array.isArray(data.data) ? data.data : [];
        activeIndex.value = 0;
    } catch {
        slides.value = [];
    } finally {
        loading.value = false;
    }
};

const goTo = (index) => {
    if (!slides.value.length) {
        return;
    }

    activeIndex.value = (index + slides.value.length) % slides.value.length;
};

const next = () => goTo(activeIndex.value + 1);
const previous = () => goTo(activeIndex.value - 1);

const startAutoplay = () => {
    stopAutoplay();
    timer.value = window.setInterval(() => {
        if (hasMultiple.value) {
            next();
        }
    }, 6500);
};

const stopAutoplay = () => {
    if (timer.value) {
        window.clearInterval(timer.value);
        timer.value = null;
    }
};

onMounted(async () => {
    await loadSlides();
    startAutoplay();
});

onBeforeUnmount(stopAutoplay);
</script>

<template>
    <section
        v-if="activeSlide"
        class="hero-cinematic relative overflow-hidden border-b border-[#2A2A2A]"
        @mouseenter="stopAutoplay"
        @mouseleave="startAutoplay"
    >
        <Transition name="hero-fade" mode="out-in">
            <div :key="activeSlide.id" class="relative">
                <img
                    v-if="activeSlide.background_image"
                    :src="activeSlide.background_image"
                    :alt="activeSlide.background_alt || activeSlide.title"
                    class="absolute inset-0 h-full w-full object-cover"
                    loading="eager"
                >
                <div v-else class="hero-metal"></div>
                <div class="hero-grid"></div>
                <div class="hero-glow"></div>
                <div class="absolute inset-0 bg-black" :style="{ opacity: Number(activeSlide.overlay_opacity ?? 0.55) }"></div>

                <div
                    class="relative mx-auto grid min-h-[680px] max-w-7xl items-center gap-10 px-6 py-20"
                    :class="isCentered ? 'text-center lg:grid-cols-1' : 'lg:grid-cols-[1fr_1fr]'"
                    :style="{ color: textColor }"
                >
                    <div :class="[{ 'lg:order-1': contentFirst, 'lg:order-2': !contentFirst && !isCentered }, isCentered ? 'mx-auto max-w-3xl' : '']">
                        <a
                            v-if="activeSlide.badge?.text"
                            :href="activeSlide.badge.url || '#'"
                            class="mb-5 inline-flex rounded-full border px-3 py-1 text-xs font-bold"
                            :style="{ borderColor: accentColor, color: accentColor }"
                        >
                            {{ activeSlide.badge.text }}
                        </a>
                        <p v-if="activeSlide.eyebrow_text" class="site-gold-kicker" :style="{ color: accentColor }">
                            {{ activeSlide.eyebrow_text }}
                        </p>
                        <h1 class="mt-5 max-w-3xl text-4xl font-black leading-tight md:text-7xl" :class="{ 'mx-auto': isCentered }">
                            {{ activeSlide.title }}
                        </h1>
                        <p v-if="activeSlide.subtitle" class="mt-4 text-xl font-bold" :style="{ color: accentColor }">
                            {{ activeSlide.subtitle }}
                        </p>
                        <p v-if="activeSlide.description" class="mt-6 max-w-2xl text-base leading-8 text-[#D6D6D6]" :class="{ 'mx-auto': isCentered }">
                            {{ activeSlide.description }}
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3" :class="{ 'justify-center': isCentered }">
                            <a
                                v-if="activeSlide.buttons?.primary?.text"
                                :href="activeSlide.buttons.primary.url || '#'"
                                class="site-btn-primary"
                                :style="{ backgroundColor: buttonColor, borderColor: buttonColor }"
                            >
                                {{ activeSlide.buttons.primary.text }}
                            </a>
                            <a
                                v-if="activeSlide.buttons?.secondary?.text"
                                :href="activeSlide.buttons.secondary.url || '#'"
                                class="site-btn-secondary"
                            >
                                {{ activeSlide.buttons.secondary.text }}
                            </a>
                        </div>
                        <div v-if="activeSlide.stats?.length" class="mt-8 grid max-w-xl grid-cols-3 gap-3 text-center" :class="{ 'mx-auto': isCentered }">
                            <div v-for="stat in activeSlide.stats" :key="`${stat.label}-${stat.value}`" class="hero-stat">
                                <strong :style="{ color: accentColor }">{{ stat.value }}</strong>
                                <span>{{ stat.label }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="!isCentered" class="relative" :class="{ 'lg:order-2': contentFirst, 'lg:order-1': !contentFirst }">
                        <div class="hero-product-glow"></div>
                        <div class="hero-product-panel">
                            <img
                                v-if="activeSlide.foreground_image"
                                :src="activeSlide.foreground_image"
                                :alt="activeSlide.foreground_alt || activeSlide.title"
                                class="h-[480px] w-full object-cover"
                                :loading="activeIndex === 0 ? 'eager' : 'lazy'"
                            >
                            <div v-else class="flex h-[480px] items-center justify-center bg-white/5 text-white/60">
                                {{ activeSlide.title }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <div v-if="hasMultiple" class="absolute bottom-6 left-1/2 z-10 flex -translate-x-1/2 items-center gap-3">
            <Button icon="pi pi-angle-right" rounded severity="secondary" text aria-label="قبلی" @click="previous" />
            <button
                v-for="(_, index) in slides"
                :key="index"
                type="button"
                class="h-2.5 w-2.5 rounded-full border border-white/70 transition"
                :class="index === activeIndex ? 'bg-white' : 'bg-white/20'"
                :aria-label="`اسلاید ${index + 1}`"
                @click="goTo(index)"
            />
            <Button icon="pi pi-angle-left" rounded severity="secondary" text aria-label="بعدی" @click="next" />
        </div>
    </section>
</template>
