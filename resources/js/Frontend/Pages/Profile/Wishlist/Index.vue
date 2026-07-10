<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FrontLayout from '../../../Layouts/FrontLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';

const props = defineProps({
    customer: { type: Object, required: true },
    products: { type: Object, required: true },
});

const removedProductIds = ref([]);

const visibleProducts = computed(() => {
    const removed = new Set(removedProductIds.value);

    return (props.products.data || []).filter((product) => !removed.has(product.id));
});

const total = computed(() => Math.max(Number(props.products.total || 0) - removedProductIds.value.length, 0));

const logoutCustomer = () => {
    router.post('/customer/logout', {}, {
        preserveScroll: true,
        onSuccess: () => router.visit('/'),
    });
};

const onWishlistUpdated = ({ product, is_wishlisted }) => {
    if (!is_wishlisted && product?.id && !removedProductIds.value.includes(product.id)) {
        removedProductIds.value = [...removedProductIds.value, product.id];
    }
};
</script>

<template>
    <Head>
        <title>علاقه‌مندی‌ها | MotoPart</title>
    </Head>

    <FrontLayout>
        <main class="mx-auto max-w-7xl px-4 py-8 md:px-6" dir="rtl">
            <nav class="mb-5 text-sm text-surface-500">
                <Link href="/" class="hover:text-surface-800">خانه</Link>
                <i class="pi pi-angle-left mx-2 text-xs" />
                <span class="text-surface-900">علاقه‌مندی‌ها</span>
            </nav>

            <div class="grid gap-5 lg:grid-cols-[280px_1fr] lg:items-start">
                <aside class="space-y-4 lg:sticky lg:top-32">
                    <section class="rounded-lg border border-surface-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#1A1A1A] text-lg font-black text-[#D4A017]">
                                {{ (props.customer.name || props.customer.phone || 'م').slice(0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <h2 class="truncate text-base font-black text-surface-950">
                                    {{ props.customer.name || 'مشتری MotoPart' }}
                                </h2>
                                <p class="mt-1 text-sm text-surface-500">{{ props.customer.phone || '-' }}</p>
                            </div>
                        </div>
                    </section>

                    <nav class="overflow-hidden rounded-lg border border-surface-200 bg-white shadow-sm">
                        <Link
                            href="/profile/orders"
                            class="flex items-center justify-between px-4 py-4 text-sm font-bold text-surface-700 hover:bg-surface-50"
                        >
                            <span>سفارش‌های من</span>
                            <i class="pi pi-shopping-bag text-surface-400"></i>
                        </Link>

                        <Link
                            href="/profile/wishlist"
                            class="flex items-center justify-between border-r-4 border-[#D4A017] border-t border-surface-100 bg-[#D4A017]/10 px-4 py-4 text-sm font-black text-surface-950"
                        >
                            <span>علاقه‌مندی‌ها</span>
                            <i class="pi pi-heart text-[#D4A017]"></i>
                        </Link>

                        <Link
                            href="/profile/addresses"
                            class="flex items-center justify-between border-t border-surface-100 px-4 py-4 text-sm font-bold text-surface-700 hover:bg-surface-50"
                        >
                            <span>آدرس‌ها</span>
                            <i class="pi pi-map-marker text-surface-400"></i>
                        </Link>

                        <button
                            type="button"
                            class="flex w-full items-center justify-between border-t border-surface-100 px-4 py-4 text-sm font-bold text-red-600 hover:bg-red-50"
                            @click="logoutCustomer"
                        >
                            <span>خروج از حساب کاربری</span>
                            <i class="pi pi-sign-out"></i>
                        </button>
                    </nav>
                </aside>

                <section class="min-w-0 rounded-lg border border-surface-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-2 border-b border-surface-100 px-5 py-5 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-black text-surface-950">علاقه‌مندی‌ها</h1>
                            <p class="mt-1 text-sm text-surface-500">محصولاتی که برای خرید بعدی نگه داشته‌اید.</p>
                        </div>
                        <span class="text-sm font-bold text-surface-500">
                            {{ total.toLocaleString('fa-IR') }} محصول
                        </span>
                    </div>

                    <div class="p-5">
                        <section
                            v-if="!visibleProducts.length"
                            class="rounded-lg border border-dashed border-surface-300 bg-surface-50 px-4 py-14 text-center"
                        >
                            <i class="pi pi-heart mb-4 block text-4xl text-[#D4A017]" />
                            <h2 class="text-lg font-black text-surface-950">هنوز محصولی به علاقه‌مندی‌ها اضافه نکرده‌اید.</h2>
                            <p class="mt-2 text-sm text-surface-500">محصولات مورد علاقه‌تان را با زدن آیکن قلب ذخیره کنید.</p>
                            <Link
                                href="/products"
                                class="mt-5 inline-flex items-center justify-center rounded-lg bg-[#1A1A1A] px-5 py-3 text-sm font-black text-white hover:bg-black"
                            >
                                مشاهده محصولات
                            </Link>
                        </section>

                        <section v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <ProductCard
                                v-for="product in visibleProducts"
                                :key="product.id"
                                :product="product"
                                @wishlist-updated="onWishlistUpdated"
                            />
                        </section>

                        <nav v-if="props.products.links?.length > 3" class="flex flex-wrap items-center justify-center gap-2 pt-6">
                            <Link
                                v-for="link in props.products.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'min-w-10 rounded-lg border px-3 py-2 text-center text-sm font-bold',
                                    link.active
                                        ? 'border-[#D4A017] bg-[#D4A017] text-black'
                                        : 'border-surface-200 bg-white text-surface-700 hover:border-surface-400',
                                    !link.url ? 'pointer-events-none opacity-40' : '',
                                ]"
                                v-html="link.label"
                            />
                        </nav>
                    </div>
                </section>
            </div>
        </main>
    </FrontLayout>
</template>
