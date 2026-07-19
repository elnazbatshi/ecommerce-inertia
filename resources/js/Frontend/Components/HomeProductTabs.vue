<script setup>
import { computed, ref } from 'vue';
import ProductCard from '@/Components/Site/ProductCard.vue';

const props = defineProps({
    productRankings: {
        type: Object,
        default: () => ({
            best_sellers: [],
            most_viewed: [],
            most_reviewed: [],
        }),
    },
});

const tabs = [
    { key: 'best_sellers', label: 'پرفروش ترین', metricType: 'sales' },
    { key: 'most_viewed', label: 'پربازدیدترین', metricType: 'views' },
    { key: 'most_reviewed', label: 'پربحث ترین', metricType: 'reviews' },
];

const activeTab = ref(tabs[0].key);

const activeProducts = computed(() => props.productRankings?.[activeTab.value] ?? []);
const activeMetricType = computed(() => tabs.find((tab) => tab.key === activeTab.value)?.metricType ?? null);
</script>

<template>
    <section v-if="tabs.some((tab) => productRankings?.[tab.key]?.length)" class="mx-auto max-w-7xl bg-white px-6 py-14">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-black text-[#D4A017]">انتخاب خریداران</p>
                <h2 class="mt-2 text-2xl font-black text-[#111111] md:text-3xl">محصولات محبوب فروشگاه</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-lg border px-4 py-2 text-sm font-black transition"
                    :class="activeTab === tab.key ? 'border-[#111111] bg-[#111111] text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-[#D4A017] hover:text-[#D4A017]'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <ProductCard
                v-for="product in activeProducts"
                :key="product.id"
                :product="product"
                :metricType="activeMetricType"
            />
        </div>
    </section>
</template>
