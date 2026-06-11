<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import FrontLayout from '../../Layouts/FrontLayout.vue';
import { useCart } from '@/Composables/useCart';

defineProps({
    order: { type: Object, required: true },
});

const formatPrice = (value) => `${Number(value || 0).toLocaleString('fa-IR')} تومان`;
const { clearCart } = useCart();

onMounted(() => clearCart());
</script>

<template>
    <Head>
        <title>سفارش ثبت شد | MotoPart</title>
    </Head>

    <FrontLayout>
        <main class="mx-auto max-w-3xl px-4 py-12 md:px-6" dir="rtl">
            <section class="rounded-xl border border-surface-200 bg-white p-6 text-center shadow-sm">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#D4A017]/15 text-3xl text-[#D4A017]">
                    <i class="pi pi-check" />
                </div>
                <h1 class="text-2xl font-black text-surface-950">سفارش شما ثبت شد</h1>
                <p class="mt-2 text-sm text-surface-500">سفارش با وضعیت pending ساخته شد و منتظر ادامه فرایند پرداخت/پردازش است.</p>

                <div class="mt-6 rounded-lg bg-surface-50 p-4 text-right">
                    <div class="flex justify-between py-2">
                        <span class="text-surface-500">شماره سفارش</span>
                        <strong class="text-surface-950">{{ order.order_number }}</strong>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-surface-500">وضعیت سفارش</span>
                        <strong class="text-surface-950">{{ order.status }}</strong>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-surface-500">مبلغ نهایی</span>
                        <strong class="text-surface-950">{{ formatPrice(order.total) }}</strong>
                    </div>
                </div>

                <div class="mt-6 space-y-2 text-right">
                    <div v-for="item in order.items" :key="item.name" class="flex justify-between rounded-lg border border-surface-100 p-3 text-sm">
                        <span>{{ item.name }} × {{ item.quantity.toLocaleString('fa-IR') }}</span>
                        <strong>{{ formatPrice(item.total_price) }}</strong>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <Link href="/" class="rounded-lg border border-surface-200 px-4 py-2 font-bold text-surface-800">بازگشت به خانه</Link>
                    <Link href="/products" class="rounded-lg bg-[#D4A017] px-4 py-2 font-black text-black">ادامه خرید</Link>
                </div>
            </section>
        </main>
    </FrontLayout>
</template>
