<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    brands: {
        type: Array,
        default: () => []
    }
});

const fallbackBrands = ['Motul', 'Castrol', 'Bosch', 'NGK', 'K&N', 'Liqui Moly'];
const apiBrands = ref([]);
let brandsController = null;

const isCanceledRequest = (error) => axios.isCancel(error)
    || error?.code === 'ERR_CANCELED'
    || error?.message === 'Request aborted'
    || error?.name === 'CanceledError';

const visibleBrands = computed(() => {
    if (props.brands.length) {
        return props.brands;
    }

    return apiBrands.value.length ? apiBrands.value : fallbackBrands;
});

onMounted(async () => {
    if (props.brands.length) {
        return;
    }

    brandsController?.abort();
    brandsController = new AbortController();

    try {
        const response = await axios.get('/api/frontend/brands', {
            signal: brandsController.signal
        });
        apiBrands.value = response.data.data;
    } catch (error) {
        if (isCanceledRequest(error)) {
            return;
        }

        console.warn('Frontend brands API failed; using fallback brands.', error);
        apiBrands.value = fallbackBrands;
    }
});

onBeforeUnmount(() => {
    brandsController?.abort();
});
</script>

<template>
    <section class="border-t border-[#2A2A2A] bg-[#111111] py-10">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-xl font-black text-white">برندهای منتخب</h2>
                <span class="text-sm text-[#D4A017]">MotoPart supply network</span>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-6">
                <a
                    v-for="brand in visibleBrands"
                    :key="brand.slug || brand.name || brand"
                    :href="brand.url || (brand.slug ? `/brand/${brand.slug}` : '#')"
                    class="border border-[#2A2A2A] bg-[#1A1A1A] px-4 py-5 text-center text-sm font-black text-white transition hover:border-[#D4A017] hover:text-[#D4A017]"
                >
                    {{ brand.title || brand.name || brand }}
                </a>
            </div>
        </div>
    </section>
</template>
