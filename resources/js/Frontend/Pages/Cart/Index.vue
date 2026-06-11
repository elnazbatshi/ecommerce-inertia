<script setup>
import axios from 'axios';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import FrontLayout from '../../Layouts/FrontLayout.vue';
import CustomerAuthModal from '../../Components/CustomerAuthModal.vue';
import { useCart } from '@/Composables/useCart';

const toast = useToast();
const page = usePage();
const { items, count, subtotal, updateQuantity, removeItem, clearCart } = useCart();
const authModalVisible = ref(false);
const checkoutLoading = ref(false);
const customer = computed(() => page.props.customer || null);

const shippingCost = computed(() => (subtotal.value > 0 && subtotal.value < 2500000 ? 120000 : 0));
const total = computed(() => subtotal.value + shippingCost.value);

const formatPrice = (value) => `${Number(value || 0).toLocaleString('fa-IR')} تومان`;
const productUrl = (item) => item.slug ? `/products/${item.slug}` : '/products';

const syncAndGoCheckout = async () => {
    checkoutLoading.value = true;

    try {
        await axios.post('/cart/sync', { items: items.value });
        router.visit('/checkout');
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'تکمیل خرید',
            detail: error.response?.data?.message || 'اتصال سبد خرید انجام نشد.',
            life: 2200,
        });
    } finally {
        checkoutLoading.value = false;
    }
};

const checkout = () => {
    if (!customer.value) {
        authModalVisible.value = true;
        return;
    }

    syncAndGoCheckout();
};
</script>

<template>
    <Head>
        <title>سبد خرید | MotoPart</title>
    </Head>

    <FrontLayout>
        <main class="mx-auto max-w-7xl px-4 py-8 pb-28 md:px-6 md:pb-8">
            <nav class="mb-4 text-sm text-surface-500">
                <Link href="/" class="hover:text-surface-800">خانه</Link>
                <i class="pi pi-angle-left mx-2 text-xs" />
                <span class="text-surface-900">سبد خرید</span>
            </nav>

            <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-surface-950 md:text-3xl">سبد خرید</h1>
                    <p class="mt-1 text-sm text-surface-500">{{ count.toLocaleString('fa-IR') }} کالا در سبد شماست</p>
                </div>
                <Button v-if="items.length" label="خالی کردن سبد" icon="pi pi-trash" severity="danger" text @click="clearCart" />
            </div>

            <div v-if="items.length" class="grid grid-cols-1 gap-5 lg:grid-cols-[1fr_380px]">
                <section class="space-y-3">
                    <article
                        v-for="item in items"
                        :key="item.key"
                        class="grid grid-cols-[96px_1fr] gap-4 rounded-2xl border border-surface-200 bg-white p-4 shadow-sm md:grid-cols-[120px_1fr_auto]"
                    >
                        <Link :href="productUrl(item)" class="overflow-hidden rounded-xl bg-surface-100">
                            <img
                                :src="item.image || 'https://picsum.photos/seed/cart-product/320/260'"
                                :alt="item.name"
                                class="h-24 w-full object-cover md:h-28"
                            />
                        </Link>

                        <div class="min-w-0">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <Tag v-if="item.brand" :value="item.brand" severity="secondary" />
                                <Tag v-if="item.variant_label" :value="item.variant_label" severity="info" />
                            </div>
                            <Link :href="productUrl(item)" class="line-clamp-2 text-base font-black text-surface-950 hover:text-[#D4A017]">
                                {{ item.name }}
                            </Link>
                            <p class="mt-2 text-xs text-surface-500">کد کالا: {{ item.sku || '-' }}</p>
                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <InputNumber
                                    :model-value="item.quantity"
                                    showButtons
                                    buttonLayout="horizontal"
                                    :min="1"
                                    :max="Math.max(1, item.stock || 1)"
                                    inputClass="w-14 text-center"
                                    @update:model-value="updateQuantity(item.key, $event)"
                                />
                                <span class="text-xs text-surface-500">موجودی: {{ Number(item.stock || 0).toLocaleString('fa-IR') }}</span>
                            </div>
                        </div>

                        <div class="col-span-2 flex items-end justify-between border-t border-surface-100 pt-3 md:col-span-1 md:block md:border-t-0 md:pt-0 md:text-left">
                            <div>
                                <div class="text-lg font-black text-surface-950">{{ formatPrice(item.price * item.quantity) }}</div>
                                <div v-if="item.old_price" class="mt-1 text-xs text-surface-400 line-through">{{ formatPrice(item.old_price * item.quantity) }}</div>
                            </div>
                            <Button icon="pi pi-times" rounded text severity="danger" @click="removeItem(item.key)" />
                        </div>
                    </article>
                </section>

                <aside class="lg:sticky lg:top-32 lg:self-start">
                    <div class="rounded-2xl border border-surface-200 bg-white p-5 shadow-sm">
                        <h2 class="mb-4 text-xl font-black text-surface-950">خلاصه سفارش</h2>
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-surface-500">جمع کالاها</dt>
                                <dd class="font-bold text-surface-900">{{ formatPrice(subtotal) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-surface-500">ارسال</dt>
                                <dd class="font-bold text-surface-900">{{ shippingCost ? formatPrice(shippingCost) : 'رایگان' }}</dd>
                            </div>
                            <div class="border-t border-surface-200 pt-3 flex items-center justify-between">
                                <dt class="text-base font-black text-surface-950">مبلغ نهایی</dt>
                                <dd class="text-xl font-black text-surface-950">{{ formatPrice(total) }}</dd>
                            </div>
                        </dl>

                        <Button label="ادامه فرایند خرید" icon="pi pi-credit-card" class="mt-5 w-full" :loading="checkoutLoading" @click="checkout" />
                        <Link href="/products" class="mt-3 block text-center text-sm font-bold text-[#D4A017]">ادامه خرید</Link>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 rounded-2xl bg-[#1A1A1A] p-4 text-xs text-white">
                        <div class="flex items-center gap-2"><i class="pi pi-verified text-[#D4A017]" /> اصالت کالا</div>
                        <div class="flex items-center gap-2"><i class="pi pi-truck text-[#D4A017]" /> ارسال سریع</div>
                        <div class="flex items-center gap-2"><i class="pi pi-lock text-[#D4A017]" /> پرداخت امن</div>
                        <div class="flex items-center gap-2"><i class="pi pi-refresh text-[#D4A017]" /> ضمانت بازگشت</div>
                    </div>
                </aside>
            </div>

            <section v-else class="rounded-2xl border border-dashed border-surface-300 bg-white px-4 py-16 text-center">
                <i class="pi pi-shopping-cart mb-4 block text-5xl text-[#D4A017]" />
                <h2 class="text-xl font-black text-surface-950">سبد خرید شما خالی است</h2>
                <p class="mt-2 text-sm text-surface-500">از آرشیو محصولات، کالاهای مورد نیازتان را انتخاب کنید.</p>
                <Link href="/products" class="mt-5 inline-flex">
                    <Button label="مشاهده محصولات" icon="pi pi-arrow-left" />
                </Link>
            </section>
        </main>

        <div v-if="items.length" class="fixed inset-x-0 bottom-0 z-20 border-t border-surface-200 bg-white/95 p-3 shadow-lg backdrop-blur md:hidden">
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <div class="text-xs text-surface-500">مبلغ نهایی</div>
                    <div class="font-black text-surface-950">{{ formatPrice(total) }}</div>
                </div>
                <Button label="ادامه خرید" icon="pi pi-credit-card" :loading="checkoutLoading" @click="checkout" />
            </div>
        </div>

        <CustomerAuthModal
            v-model:visible="authModalVisible"
            redirect-after-login="/checkout"
        />
    </FrontLayout>
</template>
