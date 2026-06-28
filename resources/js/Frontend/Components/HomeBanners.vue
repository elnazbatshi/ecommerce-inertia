<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    placement: { type: String, required: true },
    sectionKey: { type: String, default: '' },
});

const sections = ref([]);
let controller = null;

const visibleSections = computed(() => {
    if (!props.sectionKey) {
        return sections.value;
    }

    return sections.value.filter((section) => section.key === props.sectionKey);
});

const loadSections = async () => {
    controller?.abort();
    controller = new AbortController();

    try {
        const { data } = await axios.get('/api/frontend/banner-sections', {
            params: { placement: props.placement },
            signal: controller.signal,
        });

        sections.value = data.data ?? [];
    } catch (error) {
        if (error?.code !== 'ERR_CANCELED') {
            sections.value = [];
        }
    } finally {
        controller = null;
    }
};

const bannerImage = (banner) => banner.mobile_image || banner.image;

const normalizeColor = (value, fallback) => {
    if (!value) {
        return fallback;
    }

    return String(value).startsWith('#') ? value : `#${value}`;
};

const bannerStyle = (banner) => ({
    backgroundColor: normalizeColor(banner.background_color, '#111111'),
    color: normalizeColor(banner.text_color, '#ffffff'),
});

const bannerTextStyle = (banner, opacity = 1) => ({
    color: normalizeColor(banner.text_color, '#ffffff'),
    opacity,
});

const bannerClass = (layout, index) => {
    if (layout === 'mixed_grid') {
        return index === 2 ? 'md:col-span-1' : (index === 3 ? 'md:col-span-2' : '');
    }

    return '';
};

const gridClass = (layout) => ({
    full_width: 'grid-cols-1',
    two_columns: 'grid-cols-1 md:grid-cols-2',
    four_grid: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    mixed_grid: 'grid-cols-1 md:grid-cols-3',
    horizontal_scroll: 'auto-cols-[82%] grid-flow-col overflow-x-auto md:auto-cols-[32rem]',
}[layout] || 'grid-cols-1');

onMounted(loadSections);

onBeforeUnmount(() => {
    controller?.abort();
});
</script>

<template>
    <section v-if="visibleSections.length" class="mx-auto max-w-7xl px-6 py-10">
        <div v-for="section in visibleSections" :key="section.id" class="mb-8 last:mb-0">
            <div class="grid gap-4" :class="gridClass(section.layout)">
                <component
                    :is="banner.link_url ? 'a' : 'div'"
                    v-for="(banner, index) in section.banners"
                    :key="banner.id"
                    :href="banner.link_url || undefined"
                    class="group relative block min-h-[180px] overflow-hidden rounded-2xl border border-[#2A2A2A] bg-[#111] transition duration-300 hover:scale-[1.015] md:min-h-[220px]"
                    :class="bannerClass(section.layout, index)"
                    :style="bannerStyle(banner)"
                >
                    <img
                        v-if="bannerImage(banner)"
                        :src="bannerImage(banner)"
                        :alt="banner.title || section.title"
                        class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        loading="lazy"
                    >
                    <div v-else class="absolute inset-0 bg-[linear-gradient(135deg,#111_0%,#242424_52%,#D4A017_160%)]"></div>
                    <div class="absolute inset-0 bg-black/35"></div>

                    <div class="relative flex min-h-[180px] flex-col justify-end p-5 md:min-h-[220px] md:p-7">
                        <p
                            v-if="banner.subtitle"
                            class="text-xs font-black uppercase tracking-[0.2em]"
                            :style="bannerTextStyle(banner, 0.82)"
                        >
                            {{ banner.subtitle }}
                        </p>
                        <h3
                            v-if="banner.title"
                            class="mt-2 max-w-xl text-2xl font-black leading-tight"
                            :style="bannerTextStyle(banner)"
                        >
                            {{ banner.title }}
                        </h3>
                        <p
                            v-if="banner.description"
                            class="mt-2 max-w-xl text-sm leading-7"
                            :style="bannerTextStyle(banner, 0.82)"
                        >
                            {{ banner.description }}
                        </p>
                        <span v-if="banner.button_text" class="mt-4 inline-flex w-fit rounded-lg bg-[#D4A017] px-4 py-2 text-sm font-black text-[#111]">
                            {{ banner.button_text }}
                        </span>
                    </div>
                </component>
            </div>
        </div>
    </section>
</template>
