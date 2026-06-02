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
        <div class="mx-auto flex max-w-7xl flex-wrap gap-3 px-6 py-6">
            <a
                v-for="category in visibleCategories"
                :key="category.slug || category.title || category"
                :href="category.url || `/category/${category.slug}`"
                class="border border-[#E5E5E5] bg-white px-4 py-2 text-sm font-bold text-[#1A1A1A] transition hover:border-[#D4A017] hover:text-[#D4A017]"
            >
                {{ category.title || category.name || category }}
            </a>
        </div>
    </section>
</template>
