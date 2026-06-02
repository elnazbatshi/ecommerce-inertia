<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FrontLayout from '../../Layouts/FrontLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import ProductArchiveFilters from '@/Components/Site/ProductArchiveFilters.vue';
import ProductArchiveSort from '@/Components/Site/ProductArchiveSort.vue';
import ProductGridToggle from '@/Components/Site/ProductGridToggle.vue';

const props = defineProps({
    category: { type: Object, required: true },
    products: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 12 }) },
    brands: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const mode = ref('grid');
const sort = ref('newest');

const items = computed(() => props.products?.data ?? []);
const total = computed(() => Number(props.products?.total ?? items.value.length ?? 0));

const mockProducts = [
    { id: 1, image: 'https://picsum.photos/seed/c1/600/420', brand: 'Total', name: 'روغن موتور 10W-40', feature: 'کارکرد پایدار در سفرهای طولانی', price: 780000, oldPrice: 910000, inStock: true, isNew: true },
    { id: 2, image: 'https://picsum.photos/seed/c2/600/420', brand: 'Castrol', name: 'روغن موتور 5W-30 GTX', feature: 'شروع نرم موتور در هوای سرد', price: 1240000, oldPrice: null, inStock: true, isNew: false },
    { id: 3, image: 'https://picsum.photos/seed/c3/600/420', brand: 'Motul', name: 'روغن موتور سیکلت 20W-50', feature: 'مناسب انجین‌های کارکرد بالا', price: 690000, oldPrice: 760000, inStock: false, isNew: false },
];

const displayItems = computed(() => (items.value.length ? items.value : mockProducts));

const onPage = (event) => {
    const page = event.page + 1;
    router.get(`/category/${props.category.slug}`, { page }, { preserveState: true, preserveScroll: true, replace: true });
};
</script>

<template>
    <Head>
        <title>{{ category.name }} | MotoPart</title>
    </Head>

    <FrontLayout>
        <section class="mx-auto max-w-7xl px-4 py-8 md:px-6">
            <nav class="mb-3 text-sm text-surface-500">
                <Link href="/" class="hover:text-surface-700">خانه</Link>
                <i class="pi pi-angle-left mx-2 text-xs" />
                <span class="text-surface-800">{{ category.name }}</span>
            </nav>

            <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-surface-900 md:text-3xl">{{ category.name }}</h1>
                    <p class="mt-1 text-sm text-surface-500">{{ total.toLocaleString('fa-IR') }} محصول</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <ProductGridToggle v-model="mode" />
                    <ProductArchiveSort v-model="sort" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[300px_1fr]">
                <div class="lg:order-1">
                    <ProductArchiveFilters :categories="categories" :brands="brands" />
                </div>

                <div class="space-y-4 lg:order-2">
                    <div
                        class="grid gap-4"
                        :class="mode === 'grid' ? 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3' : 'grid-cols-1'"
                    >
                        <ProductCard
                            v-for="product in displayItems"
                            :key="product.id"
                            :product="product"
                            :mode="mode"
                        />
                    </div>

                    <Paginator
                        v-if="props.products?.last_page > 1"
                        :first="(props.products.current_page - 1) * props.products.per_page"
                        :rows="props.products.per_page"
                        :totalRecords="props.products.total"
                        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                        @page="onPage"
                    />
                </div>
            </div>
        </section>
    </FrontLayout>
</template>
