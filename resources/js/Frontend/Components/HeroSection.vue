<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const slides = ref([]);
const activeIndex = ref(0);
const timer = ref(null);
const progressKey = ref(0);
const loading = ref(true);
const autoplayDelay = 6500;
const dragStartX = ref(0);
const dragStartY = ref(0);
const dragDeltaX = ref(0);
const isDragging = ref(false);
const dragPointerId = ref(null);

const activeSlide = computed(() => slides.value[activeIndex.value] ?? null);
const hasMultiple = computed(() => slides.value.length > 1);
const activeImage = computed(() => activeSlide.value?.background_image || activeSlide.value?.foreground_image || null);
const showOverlayContent = computed(() => activeSlide.value?.show_overlay_content !== false);
const badge = computed(() => ({
    text: activeSlide.value?.badge_text ?? activeSlide.value?.badge?.text ?? null,
    url: activeSlide.value?.badge_url ?? activeSlide.value?.badge?.url ?? '#',
}));

const normalizeColor = (value, fallback) => {
    if (!value) {
        return fallback;
    }

    return String(value).startsWith('#') ? value : `#${value}`;
};

const field = (key, nested, fallback = null) => activeSlide.value?.[key] ?? nested ?? fallback;
const textColor = computed(() => normalizeColor(activeSlide.value?.colors?.text || activeSlide.value?.text_color, '#ffffff'));
const accentColor = computed(() => normalizeColor(activeSlide.value?.colors?.accent || activeSlide.value?.accent_color, '#D4A017'));
const buttonColor = computed(() => normalizeColor(activeSlide.value?.colors?.button || activeSlide.value?.button_color, '#D4A017'));
const overlayOpacity = computed(() => {
    const value = Number(activeSlide.value?.overlay_opacity ?? 0.68);

    return Number.isFinite(value) ? Math.min(Math.max(value, 0.35), 0.85) : 0.68;
});

const primaryButton = computed(() => ({
    text: field('button_primary_text', activeSlide.value?.buttons?.primary?.text),
    url: field('button_primary_url', activeSlide.value?.buttons?.primary?.url, '#'),
}));

const secondaryButton = computed(() => ({
    text: field('button_secondary_text', activeSlide.value?.buttons?.secondary?.text),
    url: field('button_secondary_url', activeSlide.value?.buttons?.secondary?.url, '#'),
}));

const stats = computed(() => {
    if (Array.isArray(activeSlide.value?.stats) && activeSlide.value.stats.length) {
        return activeSlide.value.stats.filter((stat) => stat?.label || stat?.value);
    }

    return [1, 2, 3]
        .map((number) => ({
            label: activeSlide.value?.[`stat_${number}_label`],
            value: activeSlide.value?.[`stat_${number}_value`],
        }))
        .filter((stat) => stat.label || stat.value);
});

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

const startDrag = (event) => {
    if (!hasMultiple.value || (event.button !== undefined && event.button !== 0)) {
        return;
    }

    isDragging.value = true;
    dragPointerId.value = event.pointerId;
    dragStartX.value = event.clientX;
    dragStartY.value = event.clientY;
    dragDeltaX.value = 0;
    event.currentTarget.setPointerCapture?.(event.pointerId);
    stopAutoplay();
};

const moveDrag = (event) => {
    if (!isDragging.value || event.pointerId !== dragPointerId.value) {
        return;
    }

    const deltaY = Math.abs(event.clientY - dragStartY.value);
    dragDeltaX.value = event.clientX - dragStartX.value;

    if (Math.abs(dragDeltaX.value) > deltaY) {
        event.preventDefault();
    }
};

const endDrag = (event) => {
    if (!isDragging.value || (event?.pointerId && event.pointerId !== dragPointerId.value)) {
        return;
    }

    if (Math.abs(dragDeltaX.value) > 60) {
        dragDeltaX.value > 0 ? previous() : next();
    }

    event?.currentTarget?.releasePointerCapture?.(dragPointerId.value);
    isDragging.value = false;
    dragPointerId.value = null;
    dragDeltaX.value = 0;
    startAutoplay();
};

const stopAutoplay = () => {
    if (timer.value) {
        window.clearInterval(timer.value);
        timer.value = null;
    }
};

const startAutoplay = () => {
    stopAutoplay();

    if (!hasMultiple.value) {
        return;
    }

    timer.value = window.setInterval(next, autoplayDelay);
};

watch(activeIndex, () => {
    progressKey.value += 1;
});

watch(hasMultiple, startAutoplay);

onMounted(async () => {
    await loadSlides();
    startAutoplay();
});

onBeforeUnmount(stopAutoplay);
</script>

<template>
    <section
        v-if="activeSlide"
        class="relative isolate min-h-[560px] overflow-hidden bg-[#090a0c] sm:min-h-[600px] lg:min-h-[650px]"
        :style="{ color: textColor, '--drag-x': `${Math.max(Math.min(dragDeltaX, 80), -80)}px` }"
        @mouseenter="stopAutoplay"
        @mouseleave="startAutoplay"
        @pointerdown="startDrag"
        @pointermove="moveDrag"
        @pointerup="endDrag"
        @pointercancel="endDrag"
        @pointerleave="endDrag"
    >
        <Transition name="hero-bg-fade" mode="out-in">
            <img
                v-if="activeImage"
                :key="activeSlide.id"
                :src="activeImage"
                :alt="activeSlide.background_alt || activeSlide.title"
                class="hero-bg-image absolute inset-0 h-full w-full select-none object-cover"
                :class="{ 'is-dragging': isDragging }"
                draggable="false"
                :loading="activeIndex === 0 ? 'eager' : 'lazy'"
                @dragstart.prevent
            >
            <div v-else :key="`empty-${activeSlide.id}`" class="absolute inset-0 hero-metal"></div>
        </Transition>

        <template v-if="showOverlayContent">
            <div class="absolute inset-0 bg-gradient-to-l from-black via-black/70 to-black/20" :style="{ opacity: overlayOpacity }"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20"></div>
            <div class="absolute inset-y-0 right-0 w-full bg-[radial-gradient(circle_at_80%_42%,rgba(212,160,23,0.18),transparent_36%)]"></div>
        </template>

        <div v-if="showOverlayContent" class="site-container relative z-10 flex min-h-[560px] items-center py-14 sm:min-h-[600px] lg:min-h-[650px]">
            <div class="max-w-3xl">
                <a
                    v-if="badge.text"
                    :href="badge.url || '#'"
                    class="mb-4 inline-flex rounded-full border border-white/20 bg-black/25 px-4 py-2 text-xs font-black backdrop-blur"
                    :style="{ borderColor: accentColor, color: accentColor }"
                >
                    {{ badge.text }}
                </a>

                <p v-if="activeSlide.eyebrow_text" class="text-sm font-black uppercase tracking-wider" :style="{ color: accentColor }">
                    {{ activeSlide.eyebrow_text }}
                </p>

                <h1 v-if="activeSlide.title" class="mt-4 text-4xl font-black leading-tight text-white sm:text-5xl lg:text-7xl">
                    {{ activeSlide.title }}
                </h1>

                <p v-if="activeSlide.subtitle" class="mt-4 text-xl font-black sm:text-2xl" :style="{ color: accentColor }">
                    {{ activeSlide.subtitle }}
                </p>

                <p v-if="activeSlide.description" class="mt-5 max-w-2xl text-sm leading-8 text-[#e2e5e9] sm:text-base">
                    {{ activeSlide.description }}
                </p>

                <div v-if="primaryButton.text || secondaryButton.text" class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a
                        v-if="primaryButton.text"
                        :href="primaryButton.url || '#'"
                        class="inline-flex h-12 items-center justify-center rounded-xl px-6 text-sm font-black text-[#111111] shadow-[0_16px_36px_rgba(212,160,23,0.25)] transition hover:-translate-y-0.5"
                        :style="{ backgroundColor: buttonColor }"
                    >
                        {{ primaryButton.text }}
                    </a>
                    <a
                        v-if="secondaryButton.text"
                        :href="secondaryButton.url || '#'"
                        class="inline-flex h-12 items-center justify-center rounded-xl border border-white/20 bg-white/10 px-6 text-sm font-black text-white backdrop-blur transition hover:border-[#D4A017] hover:text-[#D4A017]"
                    >
                        {{ secondaryButton.text }}
                    </a>
                </div>

                <div v-if="stats.length" class="mt-8 grid max-w-xl grid-cols-2 gap-3 sm:grid-cols-3">
                    <div v-for="stat in stats" :key="`${stat.label}-${stat.value}`" class="rounded-2xl border border-white/15 bg-black/25 p-4 backdrop-blur">
                        <strong class="block text-2xl font-black" :style="{ color: accentColor }">{{ stat.value }}</strong>
                        <span class="mt-1 block text-xs font-bold text-white/80">{{ stat.label }}</span>
                    </div>
                </div>
            </div>
        </div>

        <template v-if="hasMultiple">
            <button
                type="button"
                class="absolute right-4 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-black/35 text-white backdrop-blur transition hover:border-[#D4A017] hover:bg-[#D4A017] hover:text-black sm:right-8"
                aria-label="اسلاید قبلی"
                @click="previous"
            >
                <i class="pi pi-angle-right"></i>
            </button>

            <button
                type="button"
                class="absolute left-4 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-black/35 text-white backdrop-blur transition hover:border-[#D4A017] hover:bg-[#D4A017] hover:text-black sm:left-8"
                aria-label="اسلاید بعدی"
                @click="next"
            >
                <i class="pi pi-angle-left"></i>
            </button>

            <div class="absolute bottom-8 left-1/2 z-20 flex -translate-x-1/2 items-center gap-2">
                <button
                    v-for="(_, index) in slides"
                    :key="index"
                    type="button"
                    class="h-2.5 rounded-full border border-white/60 transition"
                    :class="index === activeIndex ? 'w-8 bg-white' : 'w-2.5 bg-white/30 hover:bg-white/60'"
                    :aria-label="`نمایش اسلاید ${index + 1}`"
                    @click="goTo(index)"
                />
            </div>

            <div class="absolute inset-x-0 bottom-0 z-20 h-1 bg-white/10">
                <span
                    :key="progressKey"
                    class="hero-progress block h-full"
                    :style="{ backgroundColor: accentColor, animationDuration: `${autoplayDelay}ms` }"
                ></span>
            </div>
        </template>
    </section>
</template>

<style scoped>
.hero-bg-image {
    transform: translateX(0) scale(1.02);
    transition: transform 450ms ease;
    user-select: none;
    -webkit-user-drag: none;
}

.hero-bg-image.is-dragging {
    transform: translateX(var(--drag-x)) scale(1.035);
    transition: none;
}

.hero-bg-fade-enter-active,
.hero-bg-fade-leave-active {
    transition: opacity 600ms ease, transform 700ms ease;
}

.hero-bg-fade-enter-from,
.hero-bg-fade-leave-to {
    opacity: 0;
    transform: scale(1.05);
}

.hero-progress {
    animation-name: hero-progress-fill;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
    transform-origin: right center;
}

@keyframes hero-progress-fill {
    from {
        transform: scaleX(0);
    }

    to {
        transform: scaleX(1);
    }
}
</style>
