<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import useEmblaCarousel from 'embla-carousel-vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import SectionTitle from './SectionTitle.vue';

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
    {
        key: 'best_sellers',
        label: 'پرفروش‌ترین',
        metricType: 'sales',
        empty: 'هنوز فروش ثبت‌شده‌ای برای نمایش وجود ندارد.',
    },
    {
        key: 'most_viewed',
        label: 'پربازدیدترین',
        metricType: 'views',
        empty: 'هنوز بازدید کافی برای نمایش وجود ندارد.',
    },
    {
        key: 'most_reviewed',
        label: 'پربازخوردترین',
        metricType: 'reviews',
        empty: 'هنوز نظری برای محصولات ثبت نشده است.',
    },
];

const activeTab = ref('best_sellers');
const selectedSnap = ref(0);
const scrollSnaps = ref([]);

const [carouselRef, carouselApi] = useEmblaCarousel({
    align: 'start',
    direction: 'rtl',
    loop: false,
    dragFree: false,
    containScroll: 'trimSnaps',
});

const activeTabMeta = computed(() => tabs.find((tab) => tab.key === activeTab.value) ?? tabs[0]);
const activeProducts = computed(() => props.productRankings?.[activeTab.value] ?? []);
const hasAnyProducts = computed(() => tabs.some((tab) => (props.productRankings?.[tab.key] ?? []).length));
const showControls = computed(() => activeProducts.value.length > 1 && scrollSnaps.value.length > 1);

const updateCarousel = (api) => {
    selectedSnap.value = api.selectedScrollSnap();
    scrollSnaps.value = api.scrollSnapList();
};

const selectTab = async (key) => {
    activeTab.value = key;
    await nextTick();
    carouselApi.value?.reInit();
    carouselApi.value?.scrollTo(0);
    if (carouselApi.value) {
        updateCarousel(carouselApi.value);
    }
};

const scrollPrev = () => carouselApi.value?.scrollPrev();
const scrollNext = () => carouselApi.value?.scrollNext();
const scrollTo = (index) => carouselApi.value?.scrollTo(index);

watch(carouselApi, (api) => {
    if (!api) {
        return;
    }

    updateCarousel(api);
    api.on('select', updateCarousel);
    api.on('reInit', updateCarousel);
});

watch(activeProducts, async () => {
    await nextTick();
    carouselApi.value?.reInit();
    if (carouselApi.value) {
        updateCarousel(carouselApi.value);
    }
});

onBeforeUnmount(() => {
    carouselApi.value?.destroy();
});
</script>

<template>
    <section v-if="hasAnyProducts" class="mx-auto max-w-7xl bg-white px-6 py-16">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <SectionTitle
                title="محبوب ترین کالا ها"
                subtitle="محصولات برتر بر اساس فروش، بازدید و بازخورد واقعی کاربران"
            />

            <div class="flex flex-wrap gap-2 rounded-2xl border border-gray-100 bg-gray-50 p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-xl px-4 py-2 text-sm font-black transition"
                    :class="activeTab === tab.key ? 'bg-[#D4A017] text-[#111111] shadow-sm' : 'text-gray-600 hover:bg-white hover:text-[#111111]'"
                    @click="selectTab(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <Transition name="home-products-fade" mode="out-in">
            <div :key="activeTab" class="relative mt-8" dir="rtl">
                <div v-if="activeProducts.length" ref="carouselRef" class="overflow-hidden">
                    <div class="-mr-4 flex touch-pan-y">
                        <div
                            v-for="product in activeProducts"
                            :key="`${activeTab}-${product.id}`"
                            class="min-w-0 flex-[0_0_83.333%] pr-4 sm:flex-[0_0_50%] lg:flex-[0_0_33.333%] xl:flex-[0_0_25%]"
                        >
                            <ProductCard :product="product" :metricType="activeTabMeta.metricType" />
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-3xl border border-dashed border-gray-200 bg-gray-50 px-6 py-12 text-center text-sm font-bold text-gray-500">
                    {{ activeTabMeta.empty }}
                </div>

                <template v-if="showControls">
                    <button
                        type="button"
                        class="absolute right-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-gray-100 bg-white text-lg font-black text-[#111111] shadow-lg transition hover:border-[#D4A017] hover:text-[#D4A017]"
                        aria-label="محصول قبلی"
                        @click="scrollPrev"
                    >
                        ‹
                    </button>

                    <button
                        type="button"
                        class="absolute left-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-gray-100 bg-white text-lg font-black text-[#111111] shadow-lg transition hover:border-[#D4A017] hover:text-[#D4A017]"
                        aria-label="محصول بعدی"
                        @click="scrollNext"
                    >
                        ›
                    </button>

                    <div class="mt-5 flex items-center justify-center gap-2">
                        <button
                            v-for="(_, index) in scrollSnaps"
                            :key="index"
                            type="button"
                            class="h-2.5 rounded-full transition"
                            :class="selectedSnap === index ? 'w-7 bg-[#D4A017]' : 'w-2.5 bg-gray-300 hover:bg-gray-400'"
                            :aria-label="`نمایش اسلاید ${index + 1}`"
                            @click="scrollTo(index)"
                        />
                    </div>
                </template>
            </div>
        </Transition>
    </section>
</template>

<style scoped>
.home-products-fade-enter-active,
.home-products-fade-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.home-products-fade-enter-from,
.home-products-fade-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
