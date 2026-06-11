<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import FrontLayout from '../../Layouts/FrontLayout.vue';
import { useCart } from '@/Composables/useCart';

const props = defineProps({
    order: { type: Object, required: true },
});

const { clearCart } = useCart();
clearCart();

const formatPrice = (value) => `${Number(value || 0).toLocaleString('fa-IR')} تومان`;
const addressLine = computed(() => [
    props.order.address?.province,
    props.order.address?.city,
    props.order.address?.address,
].filter(Boolean).join('، '));
const paymentMessage = computed(() => {
    if (props.order.payment_status === 'paid') return 'پرداخت سفارش با موفقیت انجام شد و سفارش وارد مرحله پردازش شده است.';
    if (props.order.payment_status === 'failed') return 'پرداخت سفارش ناموفق بود. در صورت نیاز دوباره از سبد خرید اقدام کنید.';

    return 'سفارش ثبت شد و در انتظار پرداخت یا تایید است.';
});
</script>

<template>
    <Head>
        <title>سفارش ثبت شد | MotoPart</title>
    </Head>

    <FrontLayout>
        <main class="mx-auto max-w-5xl px-4 py-10 md:px-6" dir="rtl">
            <section class="rounded-2xl border border-surface-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-surface-950">سفارش شما ثبت شد</h1>
                        <p class="mt-2 text-sm text-surface-500">{{ paymentMessage }}</p>
                    </div>
                    <div class="rounded-full bg-[#D4A017]/10 px-4 py-2 font-black text-[#D4A017]">{{ props.order.order_number }}</div>
                </div>

                <div class="grid gap-5 lg:grid-cols-[1fr_320px]">
                    <section class="space-y-4">
                        <div class="rounded-xl bg-surface-50 p-4">
                            <h2 class="mb-3 text-lg font-black text-surface-950">اطلاعات سفارش</h2>
                            <div class="grid gap-3 text-sm md:grid-cols-2">
                                <div><span class="text-surface-500">وضعیت پرداخت:</span> <strong>{{ props.order.payment_status }}</strong></div>
                                <div><span class="text-surface-500">روش ارسال:</span> <strong>{{ props.order.shipping_method?.name || '-' }}</strong></div>
                                <div><span class="text-surface-500">روش پرداخت:</span> <strong>{{ props.order.payment_method?.name || '-' }}</strong></div>
                                <div><span class="text-surface-500">مبلغ کل:</span> <strong>{{ formatPrice(props.order.total) }}</strong></div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-surface-50 p-4">
                            <h2 class="mb-3 text-lg font-black text-surface-950">آدرس ارسال</h2>
                            <p class="text-sm text-surface-700">{{ addressLine || '-' }}</p>
                            <p class="mt-2 text-sm text-surface-700">گیرنده: {{ props.order.address?.recipient_name || '-' }} - {{ props.order.address?.recipient_mobile || '-' }}</p>
                            <p v-if="props.order.address?.postal_code" class="mt-2 text-sm text-surface-700">کد پستی: {{ props.order.address.postal_code }}</p>
                        </div>

                        <div class="rounded-xl bg-surface-50 p-4">
                            <h2 class="mb-3 text-lg font-black text-surface-950">اقلام سفارش</h2>
                            <div v-for="item in props.order.items" :key="`${item.name}-${item.sku || item.variant_name || item.quantity}`" class="flex items-start justify-between border-b border-surface-200 py-3 last:border-b-0">
                                <div>
                                    <div class="font-bold text-surface-900">{{ item.name }}</div>
                                    <div v-if="item.variant_name" class="mt-1 text-xs text-surface-500">{{ item.variant_name }}</div>
                                    <div v-if="item.sku" class="mt-1 text-xs text-surface-500">SKU: {{ item.sku }}</div>
                                    <div class="mt-1 text-xs text-surface-500">تعداد: {{ item.quantity.toLocaleString('fa-IR') }}</div>
                                </div>
                                <div class="font-bold text-surface-900">{{ formatPrice(item.total_price) }}</div>
                            </div>
                        </div>
                    </section>

                    <aside class="space-y-4">
                        <div class="rounded-xl border border-surface-200 bg-white p-4">
                            <div class="flex items-center justify-between"><span class="text-surface-500">جمع کالاها</span><strong>{{ formatPrice(props.order.subtotal) }}</strong></div>
                            <div class="mt-3 flex items-center justify-between"><span class="text-surface-500">ارسال</span><strong>{{ formatPrice(props.order.shipping_cost) }}</strong></div>
                            <div class="mt-3 flex items-center justify-between border-t border-surface-200 pt-3"><span class="font-black text-surface-950">مبلغ نهایی</span><strong class="text-lg font-black text-surface-950">{{ formatPrice(props.order.total) }}</strong></div>
                        </div>

                        <div class="rounded-xl bg-[#1A1A1A] p-4 text-sm text-white">
                            <div class="flex items-center gap-2"><i class="pi pi-verified text-[#D4A017]" /> اصالت کالا</div>
                            <div class="mt-2 flex items-center gap-2"><i class="pi pi-truck text-[#D4A017]" /> ارسال سریع</div>
                            <div class="mt-2 flex items-center gap-2"><i class="pi pi-lock text-[#D4A017]" /> پرداخت امن</div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <Link href="/products" class="rounded-lg bg-[#D4A017] px-4 py-3 text-center font-black text-black">ادامه خرید</Link>
                            <Link href="/" class="rounded-lg border border-surface-200 px-4 py-3 text-center font-bold text-surface-800">صفحه اصلی</Link>
                        </div>
                    </aside>
                </div>
            </section>
        </main>
    </FrontLayout>
</template>
