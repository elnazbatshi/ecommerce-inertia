<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import FrontLayout from '../../Layouts/FrontLayout.vue';

const props = defineProps({
    payment: { type: Object, required: true },
    order: { type: Object, required: true },
    routes: { type: Object, required: true },
});

const formatPrice = (value) => `${Number(value || 0).toLocaleString('fa-IR')} تومان`;

const go = (url) => {
    router.get(url, {}, { preserveScroll: true });
};
</script>

<template>
    <Head>
        <title>درگاه پرداخت آزمایشی | MotoPart</title>
    </Head>

    <FrontLayout>
        <main class="mx-auto max-w-3xl px-4 py-10 md:px-6" dir="rtl">
            <section class="rounded-2xl border border-surface-200 bg-white p-6 shadow-sm">
                <div class="mb-6 border-b border-surface-100 pb-5">
                    <p class="text-sm font-bold text-[#D4A017]">MotoPart</p>
                    <h1 class="mt-2 text-2xl font-black text-surface-950">درگاه پرداخت آزمایشی</h1>
                </div>

                <div class="grid gap-3 rounded-xl bg-surface-50 p-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-surface-500">شماره سفارش</span>
                        <strong class="text-surface-950">{{ order.order_number }}</strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-surface-500">مبلغ پرداخت</span>
                        <strong class="text-lg text-surface-950">{{ formatPrice(payment.amount) }}</strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-surface-500">Gateway</span>
                        <strong class="text-surface-950">{{ payment.gateway }}</strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-surface-500">شناسه داخلی</span>
                        <strong class="text-left text-xs text-surface-700">{{ payment.transaction_id }}</strong>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <button
                        type="button"
                        class="rounded-lg bg-[#D4A017] px-4 py-3 font-black text-black transition hover:bg-[#c29212]"
                        @click="go(routes.success)"
                    >
                        پرداخت موفق
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 font-black text-red-700 transition hover:bg-red-100"
                        @click="go(routes.fail)"
                    >
                        پرداخت ناموفق
                    </button>
                </div>

                <Link :href="routes.cart" class="mt-5 block text-center text-sm font-bold text-surface-600 hover:text-surface-950">
                    بازگشت به سبد خرید
                </Link>
            </section>
        </main>
    </FrontLayout>
</template>
