<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
});

const fallbackCategories = [
    { title: 'روغن و روانکار', slug: 'oil-lubricants', url: '/category/oil-lubricants' },
    { title: 'قطعات اصلی', slug: 'main-parts', url: '/category/main-parts' },
    { title: 'قطعات جانبی', slug: 'accessories-parts', url: '/category/accessories-parts' },
    { title: 'تجهیزات و لوازم', slug: 'equipment-tools', url: '/category/equipment-tools' }
];

const apiCategories = ref([]);
let categoriesController = null;

const isCanceledRequest = (error) => axios.isCancel(error)
    || error?.code === 'ERR_CANCELED'
    || error?.message === 'Request aborted'
    || error?.name === 'CanceledError';

const visibleCategories = computed(() => {
    if (props.categories.length) {
        return props.categories;
    }

    return apiCategories.value.length ? apiCategories.value : fallbackCategories;
});

const categoryTitle = (category) => category.title || category.name || category;

const categoryUrl = (category) => category.url || (category.slug ? `/category/${category.slug}` : '#');

const iconPaths = {
    oil: 'M56 98c0 16 10.7 26 24 26s24-10 24-26c0-13.9-16.1-34-24-52-7.9 18-24 38.1-24 52Zm14-1c0-5.4 5.2-15.7 10-26 4.8 10.3 10 20.6 10 26 0 7.5-4 12-10 12s-10-4.5-10-12Z',
    engine: 'M46 62h20V48h16v14h16l12 12h12v32h-14l-12 14H58l-12-14H34V76h12V62Zm14 16v26h30l8-10h10v-8h-10l-8-8H60Z',
    brake: 'M80 42a38 38 0 1 0 0 76 38 38 0 0 0 0-76Zm0 16a22 22 0 1 1 0 44 22 22 0 0 1 0-44Zm-7 15h14v14H73V73Z',
    battery: 'M48 62h10v-8h44v8h10v44H48V62Zm14 14v16h36V76H62Z',
    spark: 'M76 40h28L88 70h18l-34 50 8-36H62l14-44Z',
    chain: 'M56 70h24v14H56a12 12 0 0 0 0 24h24v14H56a26 26 0 0 1 0-52Zm24 10h24a26 26 0 0 1 0 52H80v-14h24a12 12 0 0 0 0-24H80V80Z',
    filter: 'M48 48h64L90 80v36H70V80L48 48Z',
    parts: 'M80 44l14 20 24 5-15 19 2 24-25-9-25 9 2-24-15-19 24-5 14-20Z',
    mirror: 'M48 66c0-14 12-24 28-24h8c16 0 28 10 28 24s-12 24-28 24h-8c-16 0-28-10-28-24Zm28 31h8v25H76V97Z',
    signal: 'M48 56l44 24-44 24V56Zm54 8h10v32h-10V64Z',
    body: 'M44 76l18-20h36l18 20-8 36H52l-8-36Zm24-6-8 10h40l-8-10H68Z',
    light: 'M48 80c0-18 14-32 32-32s32 14 32 32-14 32-32 32-32-14-32-32Zm32-18a18 18 0 1 0 0 36 18 18 0 0 0 0-36Z',
    grip: 'M42 72h76v16H42V72Zm10-18h56v12H52V54Zm0 40h56v12H52V94Z',
    tools: 'M54 46l18 18-10 10-18-18 10-10Zm26 26 34 34-12 12-34-34 12-12Zm24-26 10 10-52 52-10-10 52-52Z',
    helmet: 'M42 86c0-24 18-42 42-42 22 0 38 16 38 38v18H90l-12 16H58c-10 0-16-8-16-18V86Zm18 14h12l12-16h22v-2c0-13-9-22-22-22-14 0-24 10-24 26v14Z',
    gloves: 'M50 70c0-8 10-8 10 0V54c0-8 10-8 10 0v14h4V50c0-8 10-8 10 0v18h4V56c0-8 10-8 10 0v30l10-8c7-5 13 3 7 10l-25 30H64L50 96V70Z',
    lock: 'M56 74h8V62c0-12 9-22 22-22s22 10 22 22v12h8v44H56V74Zm22 0h16V62c0-5-3-8-8-8s-8 3-8 8v12Z',
    cover: 'M38 86c14-22 30-32 50-32 18 0 30 8 42 32l-10 30H48L38 86Zm26 14h40l-4-12c-5-12-12-18-22-18-11 0-19 7-28 20l14 10Z',
};

const hasCategoryIcon = (category) => Boolean(category.icon && iconPaths[category.icon]);

const categoryImage = (category) => {
    const image = category.image;

    if (!image && !hasCategoryIcon(category)) {
        return '/images/category-placeholder.svg';
    }

    if (!image) {
        return null;
    }

    if (image.startsWith('http') || image.startsWith('/')) {
        return image;
    }

    return `/storage/${image}`;
};

onMounted(async () => {
    if (props.categories.length) {
        return;
    }

    categoriesController?.abort();
    categoriesController = new AbortController();

    try {
        const response = await axios.get('/api/frontend/categories', {
            signal: categoriesController.signal
        });
        apiCategories.value = response.data.data;
    } catch (error) {
        if (isCanceledRequest(error)) {
            return;
        }

        console.warn('Frontend categories API failed; using fallback categories.', error);
        apiCategories.value = fallbackCategories;
    }
});

onBeforeUnmount(() => {
    categoriesController?.abort();
});
</script>

<template>
    <section class="border-y border-[#E5E5E5] bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-4 gap-x-3 gap-y-6 px-4 py-7 sm:grid-cols-6 sm:px-6 lg:grid-cols-8">
            <a
                v-for="category in visibleCategories"
                :key="category.slug || category.title || category"
                :href="categoryUrl(category)"
                class="group flex min-w-0 flex-col items-center text-center"
            >
                <span
                    class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border border-[#E5E5E5] bg-[#F7F7F7] transition duration-200 group-hover:scale-105 group-hover:border-[#D4A017]"
                >
                    <img
                        v-if="categoryImage(category)"
                        :src="categoryImage(category)"
                        :alt="categoryTitle(category)"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    >
                    <svg
                        v-else-if="hasCategoryIcon(category)"
                        class="h-12 w-12 text-[#1A1A1A] transition group-hover:text-[#D4A017]"
                        viewBox="0 0 160 160"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path :d="iconPaths[category.icon]" />
                    </svg>
                </span>
                <span class="mt-2 line-clamp-2 min-h-10 text-xs font-bold leading-5 text-[#1A1A1A] transition group-hover:text-[#D4A017] sm:text-sm">
                    {{ categoryTitle(category) }}
                </span>
            </a>
        </div>
    </section>
</template>
