<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FrontLayout from '../../Layouts/FrontLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import ProductArchiveFilters from '@/Components/Site/ProductArchiveFilters.vue';
import ProductArchiveSort from '@/Components/Site/ProductArchiveSort.vue';
import ProductGridToggle from '@/Components/Site/ProductGridToggle.vue';

const props = defineProps({
    products: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 12 }) },
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const mode = ref(props.filters.mode || 'grid');
const sort = ref(props.filters.sort || 'newest');

const items = computed(() => props.products?.data ?? []);
const total = computed(() => Number(props.products?.total ?? items.value.length ?? 0));

const visitArchive = (overrides = {}) => {
    router.get('/products', {
        ...props.filters,
        sort: sort.value,
        mode: mode.value,
        ...overrides,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const onPage = (event) => {
    visitArchive({ page: event.page + 1 });
};

const onSort = (value) => {
    sort.value = value;
    visitArchive({ sort: value, page: 1 });
};

const onMode = (value) => {
    mode.value = value;
    visitArchive({ mode: value });
};
</script>

<template>
    <Head>
        <title>محصولات | MotoPart</title>
    </Head>

    <FrontLayout>
        <section class="mx-auto max-w-7xl px-4 py-8 md:px-6">
            <nav class="mb-3 text-sm text-surface-500">
                <Link href="/" class="hover:text-surface-700">خانه</Link>
                <i class="pi pi-angle-left mx-2 text-xs" />
                <span class="text-surface-800">محصولات</span>
            </nav>

            <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-surface-900 md:text-3xl">محصولات</h1>
                    <p class="mt-1 text-sm text-surface-500">{{ total.toLocaleString('fa-IR') }} محصول</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <ProductGridToggle :model-value="mode" @update:model-value="onMode" />
                    <ProductArchiveSort :model-value="sort" @update:model-value="onSort" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[300px_1fr]">
                <div class="lg:order-1">
                    <ProductArchiveFilters :categories="categories" :brands="brands" />
                </div>

                <div class="space-y-4 lg:order-2">
                    <div
                        v-if="items.length"
                        class="grid gap-4"
                        :class="mode === 'grid' ? 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3' : 'grid-cols-1'"
                    >
                        <ProductCard
                            v-for="product in items"
                            :key="product.id"
                            :product="product"
                            :mode="mode"
                        />
                    </div>

                    <div v-else class="rounded-xl border border-dashed border-surface-300 bg-white p-8 text-center text-sm text-surface-500">
                        محصولی برای نمایش پیدا نشد.
                    </div>

                    <Paginator
                        v-if="products?.last_page > 1"
                        :first="(products.current_page - 1) * products.per_page"
                        :rows="products.per_page"
                        :totalRecords="products.total"
                        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                        @page="onPage"
                    />
                </div>
            </div>
        </section>
    </FrontLayout>
</template>
