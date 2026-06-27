<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FrontLayout from '../../../Layouts/FrontLayout.vue';

const props = defineProps({
    customer: { type: Object, required: true },
    orders: { type: Object, required: true },
});

const activeTab = ref('current');

const tabs = [
    { key: 'current', label: 'جاری', statuses: ['pending', 'processing', 'shipped'] },
    { key: 'delivered', label: 'تحویل شده', statuses: ['delivered'] },
    { key: 'returned', label: 'مرجوع شده', statuses: ['returned'] },
    { key: 'cancelled', label: 'لغو شده', statuses: ['cancelled'] },
];

const orderStatusLabels = {
    pending: 'در انتظار بررسی',
    processing: 'در حال پردازش',
    shipped: 'ارسال شده',
    delivered: 'تحویل داده شده',
    cancelled: 'لغو شده',
    returned: 'مرجوع شده',
};

const paymentStatusLabels = {
    unpaid: 'پرداخت نشده',
    paid: 'پرداخت شده',
    failed: 'ناموفق',
    refunded: 'بازگشت وجه',
};

const statusClasses = {
    pending: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    processing: 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
    shipped: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
    delivered: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    cancelled: 'bg-red-50 text-red-700 ring-1 ring-red-200',
    returned: 'bg-gray-100 text-gray-700 ring-1 ring-gray-200',
    unpaid: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    paid: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    failed: 'bg-red-50 text-red-700 ring-1 ring-red-200',
    refunded: 'bg-gray-100 text-gray-700 ring-1 ring-gray-200',
};

const allOrders = computed(() => props.orders.data || []);

const tabCount = (tab) => allOrders.value.filter((order) => tab.statuses.includes(order.status)).length;

const filteredOrders = computed(() => {
    const tab = tabs.find((item) => item.key === activeTab.value) || tabs[0];

    return allOrders.value.filter((order) => tab.statuses.includes(order.status));
});

const formatPrice = (value) => `${Number(value || 0).toLocaleString('fa-IR')} تومان`;

const formatDate = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('fa-IR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(value));
};

const statusLabel = (status) => orderStatusLabels[status] || status || '-';
const paymentStatusLabel = (status) => paymentStatusLabels[status] || status || '-';
const badgeClass = (status) => statusClasses[status] || 'bg-gray-100 text-gray-700 ring-1 ring-gray-200';

const logoutCustomer = () => {
    router.post('/customer/logout', {}, {
        preserveScroll: true,
        onSuccess: () => router.visit('/'),
    });
};
</script>

<template>
    <Head>
        <title>سفارش‌های من | MotoPart</title>
    </Head>

    <FrontLayout>
        <main class="mx-auto max-w-7xl px-4 py-8 md:px-6" dir="rtl">
            <nav class="mb-5 text-sm text-surface-500">
                <Link href="/" class="hover:text-surface-800">خانه</Link>
                <i class="pi pi-angle-left mx-2 text-xs" />
                <span class="text-surface-900">سفارش‌های من</span>
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
                            class="flex items-center justify-between border-r-4 border-[#D4A017] bg-[#D4A017]/10 px-4 py-4 text-sm font-black text-surface-950"
                        >
                            <span>سفارش‌های من</span>
                            <i class="pi pi-shopping-bag text-[#D4A017]"></i>
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
                    <div class="border-b border-surface-100 px-5 py-5">
                        <h1 class="text-2xl font-black text-surface-950">سفارش‌های من</h1>
                    </div>

                    <div class="overflow-x-auto border-b border-surface-100">
                        <div class="flex min-w-max gap-1 px-5">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                :class="[
                                    'relative flex items-center gap-2 px-4 py-4 text-sm font-black transition',
                                    activeTab === tab.key
                                        ? 'text-[#D4A017]'
                                        : 'text-surface-500 hover:text-surface-900',
                                ]"
                                @click="activeTab = tab.key"
                            >
                                <span>{{ tab.label }}</span>
                                <span class="rounded-full bg-surface-100 px-2 py-0.5 text-xs text-surface-700">
                                    {{ tabCount(tab).toLocaleString('fa-IR') }}
                                </span>
                                <span
                                    v-if="activeTab === tab.key"
                                    class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-[#D4A017]"
                                ></span>
                            </button>
                        </div>
                    </div>

                    <div class="p-5">
                        <section
                            v-if="!filteredOrders.length"
                            class="rounded-lg border border-dashed border-surface-300 bg-surface-50 px-4 py-14 text-center"
                        >
                            <i class="pi pi-box mb-4 block text-4xl text-[#D4A017]" />
                            <h2 class="text-lg font-black text-surface-950">هنوز سفارشی در این بخش ندارید.</h2>
                            <p class="mt-2 text-sm text-surface-500">بعد از ثبت سفارش، وضعیت آن در همین بخش نمایش داده می‌شود.</p>
                            <Link
                                href="/products"
                                class="mt-5 inline-flex items-center justify-center rounded-lg bg-[#1A1A1A] px-5 py-3 text-sm font-black text-white hover:bg-black"
                            >
                                مشاهده محصولات
                            </Link>
                        </section>

                        <section v-else class="space-y-4">
                            <article
                                v-for="order in filteredOrders"
                                :key="order.id"
                                class="rounded-lg border border-surface-200 bg-white p-5"
                            >
                                <div class="flex flex-col gap-4 border-b border-surface-100 pb-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h2 class="text-base font-black text-surface-950">
                                                سفارش {{ order.order_number || `#${order.id}` }}
                                            </h2>
                                            <span :class="['rounded-full px-3 py-1 text-xs font-bold', badgeClass(order.status)]">
                                                {{ statusLabel(order.status) }}
                                            </span>
                                            <span :class="['rounded-full px-3 py-1 text-xs font-bold', badgeClass(order.payment_status)]">
                                                {{ paymentStatusLabel(order.payment_status) }}
                                            </span>
                                        </div>

                                        <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-surface-500">
                                            <span>تاریخ ثبت: {{ formatDate(order.created_at) }}</span>
                                            <span>روش ارسال: {{ order.shipping_method_name || '-' }}</span>
                                            <span>روش پرداخت: {{ order.payment_method_name || '-' }}</span>
                                            <span>تعداد کالا: {{ Number(order.item_count || 0).toLocaleString('fa-IR') }}</span>
                                        </div>
                                    </div>

                                    <div class="shrink-0 text-right xl:text-left">
                                        <div class="text-xs text-surface-500">مبلغ کل</div>
                                        <div class="mt-1 text-xl font-black text-surface-950">{{ formatPrice(order.total) }}</div>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                    <div
                                        v-for="item in order.items_preview"
                                        :key="`${order.id}-${item.name}-${item.variant_name || ''}`"
                                        class="rounded-lg bg-surface-50 p-3"
                                    >
                                        <p class="line-clamp-1 text-sm font-black text-surface-900">{{ item.name || 'محصول' }}</p>
                                        <p class="mt-1 text-xs text-surface-500">
                                            تعداد: {{ Number(item.quantity || 0).toLocaleString('fa-IR') }}
                                            <span v-if="item.variant_name"> / {{ item.variant_name }}</span>
                                        </p>
                                        <p class="mt-2 text-sm font-bold text-surface-900">{{ formatPrice(item.total_price) }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <Link
                                        :href="`/order/thank-you/${order.id}`"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-surface-200 px-4 py-3 text-sm font-bold text-surface-800 transition hover:border-[#D4A017] hover:text-[#D4A017]"
                                    >
                                        <span>مشاهده جزئیات</span>
                                        <i class="pi pi-angle-left text-xs"></i>
                                    </Link>
                                </div>
                            </article>
                        </section>

                        <nav v-if="props.orders.links?.length > 3" class="flex flex-wrap items-center justify-center gap-2 pt-6">
                            <Link
                                v-for="link in props.orders.links"
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
