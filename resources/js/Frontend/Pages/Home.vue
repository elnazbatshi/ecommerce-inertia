<script setup>
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import useEmblaCarousel from 'embla-carousel-vue';
import Autoplay from 'embla-carousel-autoplay';
import FrontLayout from '../Layouts/FrontLayout.vue';
import SectionTitle from '../Components/SectionTitle.vue';
import HeroSection from '../Components/HeroSection.vue';
import VehicleFinder from '../Components/VehicleFinder.vue';
import CategoryStrip from '../Components/CategoryStrip.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import HomeProductTabs from '../Components/HomeProductTabs.vue';
import HomeBanners from '../Components/HomeBanners.vue';
import BrandSlider from '../Components/BrandSlider.vue';

const props = defineProps({
    featuredProducts: {
        type: Array,
        default: () => [],
    },
    productRankings: {
        type: Object,
        default: () => ({
            best_sellers: [],
            most_viewed: [],
            most_reviewed: [],
        }),
    },
});

const selectedProductSlide = ref(0);
const productScrollSnaps = ref([]);
const productAutoplay = Autoplay({
    delay: 4000,
    stopOnInteraction: false,
    stopOnMouseEnter: false,
});

const [featuredProductsCarousel, featuredProductsCarouselApi] = useEmblaCarousel(
    {
        align: 'start',
        direction: 'rtl',
        loop: true,
        dragFree: false,
        containScroll: 'trimSnaps',
    },
    [productAutoplay],
);

const updateFeaturedProductsCarousel = (api) => {
    selectedProductSlide.value = api.selectedScrollSnap();
    productScrollSnaps.value = api.scrollSnapList();
};

const scrollFeaturedProductsPrev = () => {
    featuredProductsCarouselApi.value?.scrollPrev();
};

const scrollFeaturedProductsNext = () => {
    featuredProductsCarouselApi.value?.scrollNext();
};

const scrollFeaturedProductsTo = (index) => {
    featuredProductsCarouselApi.value?.scrollTo(index);
};

const stopFeaturedProductsAutoplay = () => {
    productAutoplay.stop();
};

const playFeaturedProductsAutoplay = () => {
    productAutoplay.play();
};

watch(featuredProductsCarouselApi, (api) => {
    if (!api) {
        return;
    }

    updateFeaturedProductsCarousel(api);
    api.on('select', updateFeaturedProductsCarousel);
    api.on('reInit', updateFeaturedProductsCarousel);
});

onBeforeUnmount(() => {
    productAutoplay.stop();
});
</script>

<template>
    <Head>
        <title>MotoPart | فروشگاه تخصصی قطعات و روغن موتور</title>
    </Head>

    <FrontLayout>
        <HeroSection />
        <VehicleFinder />
        <CategoryStrip />
        <HomeProductTabs :productRankings="props.productRankings" />
        <HomeBanners placement="home_top" />
        <section v-if="props.featuredProducts?.length" id="products" class="mx-auto max-w-7xl bg-white px-6 py-16">
            <SectionTitle
                title="محصولات منتخب MotoPart"
                subtitle="پیشنهاد ویژه کارشناسان ما برای بهترین عملکرد و دوام موتور"
            />
            <div
                class="relative mt-8"
                dir="rtl"
                @mouseenter="stopFeaturedProductsAutoplay"
                @mouseleave="playFeaturedProductsAutoplay"
            >
                <div ref="featuredProductsCarousel" class="overflow-hidden">
                    <div class="-mr-4 flex touch-pan-y">
                        <div
                            v-for="product in props.featuredProducts"
                            :key="product.id || product.name"
                            class="min-w-0 flex-[0_0_83.333%] pr-4 sm:flex-[0_0_40%] lg:flex-[0_0_25%] 2xl:flex-[0_0_20%]"
                        >
                            <ProductCard :product="product" />
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    class="absolute right-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-gray-100 bg-white text-lg font-black text-[#111111] shadow-lg transition hover:border-[#D4A017] hover:text-[#D4A017]"
                    aria-label="محصول قبلی"
                    @click="scrollFeaturedProductsPrev"
                >
                    ‹
                </button>

                <button
                    type="button"
                    class="absolute left-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-gray-100 bg-white text-lg font-black text-[#111111] shadow-lg transition hover:border-[#D4A017] hover:text-[#D4A017]"
                    aria-label="محصول بعدی"
                    @click="scrollFeaturedProductsNext"
                >
                    ›
                </button>

                <div class="mt-5 flex items-center justify-center gap-2">
                    <button
                        v-for="(_, index) in productScrollSnaps"
                        :key="index"
                        type="button"
                        class="h-2.5 rounded-full transition"
                        :class="selectedProductSlide === index ? 'w-7 bg-[#D4A017]' : 'w-2.5 bg-gray-300 hover:bg-gray-400'"
                        :aria-label="`نمایش اسلاید ${index + 1}`"
                        @click="scrollFeaturedProductsTo(index)"
                    />
                </div>
            </div>
        </section>

        <HomeBanners placement="home_middle" />

        <HomeBanners placement="home_bottom" />
        <BrandSlider />
    </FrontLayout>
</template>
