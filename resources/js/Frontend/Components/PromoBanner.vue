<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const fallbackBanner = {
    id: 'fallback-promo-banner',
    eyebrow_text: 'پیشنهاد MotoPart',
    title: 'قطعات منتخب برای سرویس دوره ای',
    description: 'پکیج روغن، فیلتر، لنت و باتری را برای خودرو و موتورسیکلت از بین برندهای معتبر انتخاب کنید.',
    background_image: null,
    background_alt: 'MotoPart promotional banner',
    foreground_image: null,
    foreground_alt: 'MotoPart',
    overlay_opacity: 0.62,
    buttons: {
        primary: { text: 'مشاهده محصولات منتخب', url: '#products' },
    },
    colors: {
        text: '#ffffff',
        accent: '#D4A017',
        button: '#D4A017',
    },
};

const banners = ref([]);
let controller = null;

const activeBanner = computed(() => banners.value[0] ?? fallbackBanner);

const normalizeColor = (value, fallback) => {
    if (!value) {
        return fallback;
    }

    return String(value).startsWith('#') ? value : `#${value}`;
};

const textColor = computed(() => normalizeColor(activeBanner.value.colors?.text, '#ffffff'));
const accentColor = computed(() => normalizeColor(activeBanner.value.colors?.accent, '#D4A017'));
const buttonColor = computed(() => normalizeColor(activeBanner.value.colors?.button, '#D4A017'));

const loadBanner = async () => {
    controller?.abort();
    controller = new AbortController();

    try {
        const { data } = await axios.get('/api/frontend/hero-sliders', {
            params: { placement: 'middle_banner' },
            signal: controller.signal,
        });

        banners.value = data.data?.length ? data.data : [];
    } catch (error) {
        if (error?.code !== 'ERR_CANCELED') {
            banners.value = [];
        }
    } finally {
        controller = null;
    }
};

onMounted(loadBanner);

onBeforeUnmount(() => {
    controller?.abort();
});
</script>

<template>
    <section class="mx-auto max-w-7xl px-6 py-14">
        <a
            :href="activeBanner.buttons?.primary?.url || activeBanner.badge?.url || '#products'"
            class="group relative block min-h-[260px] overflow-hidden rounded-[8px] border border-[#2A2A2A] bg-[#111]"
            :style="{ color: textColor }"
        >
            <img
                v-if="activeBanner.background_image"
                :src="activeBanner.background_image"
                :alt="activeBanner.background_alt || activeBanner.title"
                class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                loading="lazy"
            >
            <div v-else class="absolute inset-0 bg-[linear-gradient(135deg,#111_0%,#222_48%,#D4A017_160%)]"></div>
            <div class="absolute inset-0 bg-black" :style="{ opacity: Number(activeBanner.overlay_opacity ?? 0.62) }"></div>

            <div class="relative grid min-h-[260px] items-center gap-8 p-7 md:grid-cols-[1fr_280px] md:p-10">
                <div>
                    <p v-if="activeBanner.eyebrow_text" class="text-xs font-black uppercase tracking-[0.22em]" :style="{ color: accentColor }">
                        {{ activeBanner.eyebrow_text }}
                    </p>
                    <h2 class="mt-3 max-w-3xl text-2xl font-black leading-tight md:text-4xl">
                        {{ activeBanner.title }}
                    </h2>
                    <p v-if="activeBanner.description" class="mt-4 max-w-2xl text-sm leading-7 text-white/80">
                        {{ activeBanner.description }}
                    </p>
                    <span
                        v-if="activeBanner.buttons?.primary?.text"
                        class="mt-6 inline-flex items-center justify-center rounded-[8px] px-5 py-3 text-sm font-black text-[#1A1A1A] transition group-hover:-translate-y-0.5"
                        :style="{ backgroundColor: buttonColor }"
                    >
                        {{ activeBanner.buttons.primary.text }}
                    </span>
                </div>

                <div v-if="activeBanner.foreground_image" class="hidden md:block">
                    <img
                        :src="activeBanner.foreground_image"
                        :alt="activeBanner.foreground_alt || activeBanner.title"
                        class="h-48 w-full rounded-[8px] object-cover shadow-2xl"
                        loading="lazy"
                    >
                </div>
            </div>
        </a>
    </section>
</template>
