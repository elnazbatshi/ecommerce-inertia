<script setup>
import { Head } from '@inertiajs/vue3';
import FrontLayout from '../Layouts/FrontLayout.vue';
import SectionTitle from '../Components/SectionTitle.vue';
import HeroSection from '../Components/HeroSection.vue';
import VehicleFinder from '../Components/VehicleFinder.vue';
import CategoryStrip from '../Components/CategoryStrip.vue';
import ProductCard from '../Components/ProductCard.vue';
import PromoBanner from '../Components/PromoBanner.vue';
import ReviewCard from '../Components/ReviewCard.vue';
import BrandSlider from '../Components/BrandSlider.vue';
import { reviews } from '../data/mockData';

const props = defineProps({
    featuredProducts: {
        type: Array,
        default: () => [],
    },
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
        <PromoBanner />

        <section v-if="props.featuredProducts?.length" id="products" class="mx-auto max-w-7xl bg-white px-6 py-16">
            <SectionTitle
                title="محصولات منتخب MotoPart"
                subtitle="پیشنهاد ویژه کارشناسان ما برای بهترین عملکرد و دوام موتور"
            />
            <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <ProductCard
                    v-for="product in props.featuredProducts"
                    :key="product.id || product.name"
                    :product="product"
                />
            </div>
        </section>

        <section class="reviews-luxury border-y border-[#2A2A2A]">
            <div class="mx-auto max-w-7xl px-6 py-14">
                <SectionTitle
                    title="تجربه رانندگان دیگر"
                    subtitle="بازخورد خریداران پس از نصب قطعات"
                    tone="dark"
                />
                <div class="mt-8 grid gap-5 md:grid-cols-3">
                    <ReviewCard v-for="review in reviews" :key="review.author" :review="review" />
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 py-16">
            <div class="cta-power p-8 text-center md:p-12">
                <p class="site-gold-kicker">ثبت تجربه نصب</p>
                <h2 class="mt-3 text-3xl font-black text-white md:text-5xl">
                    تجربه نصب قطعه را با رانندگان دیگر به اشتراک بگذارید
                </h2>
                <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-[#D6D6D6]">
                    بازخورد شما به انتخاب دقیق‌تر قطعات و کاهش خطای خرید در MotoPart کمک می‌کند.
                </p>
                <button class="site-btn-primary mt-7">ثبت تجربه نصب</button>
            </div>
        </section>

        <BrandSlider />
    </FrontLayout>
</template>
