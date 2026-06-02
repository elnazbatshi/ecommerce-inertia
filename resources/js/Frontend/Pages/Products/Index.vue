<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FrontLayout from '../../Layouts/FrontLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import ProductArchiveFilters from '@/Components/Site/ProductArchiveFilters.vue';
import ProductArchiveSort from '@/Components/Site/ProductArchiveSort.vue';
import ProductGridToggle from '@/Components/Site/ProductGridToggle.vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
});

const mockProducts = [
    { id: 1, image: 'https://picsum.photos/seed/p1/600/420', brand: 'Total', name: 'روغن موتور 10W-40 سینتتیک', feature: 'مناسب برای خودروهای بنزینی شهری', price: 780000, oldPrice: 910000, inStock: true, isNew: true },
    { id: 2, image: 'https://picsum.photos/seed/p2/600/420', brand: 'Castrol', name: 'روغن موتور 5W-30 GTX', feature: 'کاهش مصرف سوخت و استارت نرم', price: 1240000, oldPrice: null, inStock: true, isNew: false },
    { id: 3, image: 'https://picsum.photos/seed/p3/600/420', brand: 'Motul', name: 'روغن موتور سیکلت 20W-50', feature: 'سازگار با انجین‌های کاربراتوری', price: 690000, oldPrice: 760000, inStock: false, isNew: false },
    { id: 4, image: 'https://picsum.photos/seed/p4/600/420', brand: 'Shell', name: 'روغن موتور Helix Ultra 5W-40', feature: 'کارکرد بالا در شرایط دمایی متغیر', price: 1580000, oldPrice: 1730000, inStock: true, isNew: true },
    { id: 5, image: 'https://picsum.photos/seed/p5/600/420', brand: 'Elf', name: 'روغن موتور Evolution 900', feature: 'محافظت از سوپاپ و پیستون', price: 870000, oldPrice: null, inStock: true, isNew: false },
    { id: 6, image: 'https://picsum.photos/seed/p6/600/420', brand: 'Liqui Moly', name: 'روغن موتور Special Tec AA', feature: 'مناسب موتورهای توربو شارژ', price: 1950000, oldPrice: 2150000, inStock: true, isNew: false },
    { id: 7, image: 'https://picsum.photos/seed/p7/600/420', brand: 'Petronas', name: 'روغن موتور Syntium 3000', feature: 'محافظت پایدار در رانندگی طولانی', price: 1120000, oldPrice: null, inStock: true, isNew: false },
    { id: 8, image: 'https://picsum.photos/seed/p8/600/420', brand: 'Yacco', name: 'روغن موتور VX 1000', feature: 'مناسب خودروهای پژو و رنو', price: 940000, oldPrice: 1010000, inStock: true, isNew: true },
];

const categoryOptions = computed(() =>
    props.categories.length ? props.categories : [
        { label: 'روغن موتور خودرو', value: 'car-oil' },
        { label: 'روغن موتور سیکلت', value: 'moto-oil' },
        { label: 'فیلتر روغن', value: 'oil-filter' },
        { label: 'افزودنی', value: 'additive' },
    ]
);

const brandOptions = computed(() =>
    props.brands.length ? props.brands : [
        { label: 'Total', value: 'total' },
        { label: 'Castrol', value: 'castrol' },
        { label: 'Motul', value: 'motul' },
        { label: 'Shell', value: 'shell' },
    ]
);

const items = computed(() => (props.products.length ? props.products : mockProducts));
const mode = ref('grid');
const sort = ref('newest');
const first = ref(0);
const rows = ref(12);
const pagedItems = computed(() => items.value.slice(first.value, first.value + rows.value));
const onPage = (event) => {
    first.value = event.first;
    rows.value = event.rows;
};
</script>

<template>
    <Head>
        <title>محصولات | MotoPart</title>
    </Head>

    <FrontLayout>
        <section class="mx-auto max-w-7xl px-4 py-8 md:px-6">
            <nav class="mb-3 text-sm text-surface-500">
                <span>خانه</span>
                <i class="pi pi-angle-left mx-2 text-xs" />
                <span class="text-surface-800">محصولات</span>
            </nav>

            <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-surface-900 md:text-3xl">محصولات</h1>
                    <p class="mt-1 text-sm text-surface-500">{{ items.length.toLocaleString('fa-IR') }} محصول</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <ProductGridToggle v-model="mode" />
                    <ProductArchiveSort v-model="sort" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[300px_1fr]">
                <div class="lg:order-2">
                    <ProductArchiveFilters :categories="categoryOptions" :brands="brandOptions" />
                </div>

                <div class="space-y-4 lg:order-1">
                    <div
                        class="grid gap-4"
                        :class="mode === 'grid' ? 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3' : 'grid-cols-1'"
                    >
                        <ProductCard
                            v-for="product in pagedItems"
                            :key="product.id"
                            :product="product"
                            :mode="mode"
                        />
                    </div>

                    <Paginator
                        :first="first"
                        :rows="rows"
                        :totalRecords="items.length"
                        :rowsPerPageOptions="[12, 24, 36]"
                        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        @page="onPage"
                    />
                </div>
            </div>
        </section>
    </FrontLayout>
</template>

