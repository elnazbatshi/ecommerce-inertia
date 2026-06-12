<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import FrontLayout from '../../Layouts/FrontLayout.vue';

const props = defineProps({
    cart: { type: Object, required: true },
    customer: { type: Object, required: true },
    shippingMethods: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    provinces: { type: Array, default: () => [] },
    addresses: { type: Array, default: () => [] },
});

const defaultAddress = props.addresses.find((address) => address.is_default) || props.addresses[0] || null;

const form = useForm({
    recipient_name: defaultAddress?.receiver_name || props.customer.name || '',
    recipient_mobile: defaultAddress?.receiver_phone || props.customer.phone || '',
    province_id: defaultAddress?.province_id || '',
    city_id: defaultAddress?.city_id || '',
    address: defaultAddress?.address || '',
    postal_code: defaultAddress?.postal_code || '',
    shipping_method_id: props.shippingMethods[0]?.id || '',
    payment_method_id: props.paymentMethods[0]?.id || '',
    note: '',
});

const subtotal = computed(() => Number(props.cart.subtotal || 0));
const selectedProvince = computed(() => props.provinces.find((province) => Number(province.id) === Number(form.province_id)));
const cities = computed(() => selectedProvince.value?.cities || []);
const selectedShipping = computed(() => props.shippingMethods.find((method) => Number(method.id) === Number(form.shipping_method_id)));
const shippingCost = computed(() => {
    const method = selectedShipping.value;
    if (! method) return 0;
    if (method.free_from_amount && subtotal.value >= Number(method.free_from_amount)) return 0;

    return Number(method.base_cost || 0);
});
const total = computed(() => subtotal.value + shippingCost.value);

const formatPrice = (value) => `${Number(value || 0).toLocaleString('fa-IR')} تومان`;

const applyAddress = (address) => {
    form.recipient_name = address.receiver_name || form.recipient_name;
    form.recipient_mobile = address.receiver_phone || form.recipient_mobile;
    form.province_id = address.province_id || '';
    form.city_id = address.city_id || '';
    form.address = address.address || '';
    form.postal_code = address.postal_code || '';
};

const submit = () => {
    form.post('/checkout', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head>
        <title>تکمیل خرید | MotoPart</title>
    </Head>

    <FrontLayout>
        <main class="mx-auto max-w-7xl px-4 py-8 md:px-6" dir="rtl">
            <nav class="mb-5 flex items-center gap-2 text-sm text-surface-500">
                <Link href="/" class="hover:text-surface-900">خانه</Link>
                <span>/</span>
                <Link href="/cart" class="hover:text-surface-900">سبد خرید</Link>
                <span>/</span>
                <span class="font-bold text-surface-900">تکمیل خرید</span>
            </nav>

            <div class="mb-6">
                <h1 class="text-2xl font-black text-surface-950 md:text-3xl">تکمیل خرید</h1>
                <p class="mt-2 text-sm text-surface-500">اطلاعات ارسال، روش پرداخت و خلاصه سفارش را بررسی کنید.</p>
            </div>

            <form class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]" @submit.prevent="submit">
                <section class="space-y-5">
                    <div v-if="addresses.length" class="rounded-xl border border-surface-200 bg-white p-4">
                        <h2 class="mb-3 text-lg font-black text-surface-950">آدرس‌های قبلی</h2>
                        <div class="grid gap-3 md:grid-cols-2">
                            <button
                                v-for="address in addresses"
                                :key="address.id"
                                type="button"
                                class="rounded-lg border border-surface-200 p-3 text-right text-sm transition hover:border-[#D4A017]"
                                @click="applyAddress(address)"
                            >
                                <strong class="block text-surface-950">{{ address.title || 'آدرس مشتری' }}</strong>
                                <span class="mt-1 block text-surface-500">{{ address.province }}، {{ address.city }}</span>
                                <span class="mt-1 line-clamp-2 block text-surface-600">{{ address.address }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="rounded-xl border border-surface-200 bg-white p-5">
                        <h2 class="mb-4 text-lg font-black text-surface-950">اطلاعات گیرنده</h2>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold text-surface-800">نام گیرنده</span>
                                <input v-model="form.recipient_name" class="w-full rounded-lg border border-surface-200 px-3 py-2 outline-none focus:border-[#D4A017]" />
                                <small v-if="form.errors.recipient_name" class="mt-1 block text-red-600">{{ form.errors.recipient_name }}</small>
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold text-surface-800">موبایل گیرنده</span>
                                <input v-model="form.recipient_mobile" class="w-full rounded-lg border border-surface-200 px-3 py-2 outline-none focus:border-[#D4A017]" inputmode="tel" />
                                <small v-if="form.errors.recipient_mobile" class="mt-1 block text-red-600">{{ form.errors.recipient_mobile }}</small>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-surface-200 bg-white p-5">
                        <h2 class="mb-4 text-lg font-black text-surface-950">آدرس ارسال</h2>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold text-surface-800">استان</span>
                                <select v-model="form.province_id" class="w-full rounded-lg border border-surface-200 px-3 py-2 outline-none focus:border-[#D4A017]" @change="form.city_id = ''">
                                    <option value="">انتخاب استان</option>
                                    <option v-for="province in provinces" :key="province.id" :value="province.id">{{ province.name }}</option>
                                </select>
                                <small v-if="form.errors.province_id" class="mt-1 block text-red-600">{{ form.errors.province_id }}</small>
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold text-surface-800">شهر</span>
                                <select v-model="form.city_id" class="w-full rounded-lg border border-surface-200 px-3 py-2 outline-none focus:border-[#D4A017]">
                                    <option value="">انتخاب شهر</option>
                                    <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
                                </select>
                                <small v-if="form.errors.city_id" class="mt-1 block text-red-600">{{ form.errors.city_id }}</small>
                            </label>
                            <label class="block md:col-span-2">
                                <span class="mb-2 block text-sm font-bold text-surface-800">آدرس کامل</span>
                                <textarea v-model="form.address" rows="4" class="w-full rounded-lg border border-surface-200 px-3 py-2 outline-none focus:border-[#D4A017]" />
                                <small v-if="form.errors.address" class="mt-1 block text-red-600">{{ form.errors.address }}</small>
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold text-surface-800">کد پستی</span>
                                <input v-model="form.postal_code" class="w-full rounded-lg border border-surface-200 px-3 py-2 outline-none focus:border-[#D4A017]" />
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-surface-200 bg-white p-5">
                        <h2 class="mb-4 text-lg font-black text-surface-950">روش ارسال</h2>
                        <div class="space-y-3">
                            <label v-for="method in shippingMethods" :key="method.id" class="flex cursor-pointer items-start gap-3 rounded-lg border border-surface-200 p-3">
                                <input v-model="form.shipping_method_id" type="radio" :value="method.id" class="mt-1" />
                                <span class="flex-1">
                                    <strong class="block text-surface-950">{{ method.name }}</strong>
                                    <span v-if="method.description" class="mt-1 block text-sm text-surface-500">{{ method.description }}</span>
                                </span>
                                <span class="font-bold text-surface-900">{{ formatPrice(method.base_cost) }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-surface-200 bg-white p-5">
                        <h2 class="mb-4 text-lg font-black text-surface-950">روش پرداخت</h2>
                        <div class="space-y-3">
                            <label v-for="method in paymentMethods" :key="method.id" class="flex cursor-pointer items-start gap-3 rounded-lg border border-surface-200 p-3">
                                <input v-model="form.payment_method_id" type="radio" :value="method.id" class="mt-1" />
                                <span>
                                    <strong class="block text-surface-950">{{ method.name }}</strong>
                                    <span v-if="method.description" class="mt-1 block text-sm text-surface-500">{{ method.description }}</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-surface-200 bg-white p-5">
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-surface-800">یادداشت سفارش</span>
                            <textarea v-model="form.note" rows="3" class="w-full rounded-lg border border-surface-200 px-3 py-2 outline-none focus:border-[#D4A017]" />
                        </label>
                    </div>
                </section>

                <aside class="lg:sticky lg:top-32 lg:self-start">
                    <div class="rounded-xl border border-surface-200 bg-white p-5 shadow-sm">
                        <h2 class="mb-4 text-xl font-black text-surface-950">خلاصه سفارش</h2>
                        <div class="mb-4 space-y-3">
                            <div v-for="item in cart.items" :key="item.id" class="flex gap-3 border-b border-surface-100 pb-3 text-sm">
                                <div class="flex-1">
                                    <Link :href="`/products/${item.slug}`" class="font-bold text-surface-900 hover:text-[#D4A017]">{{ item.name }}</Link>
                                    <div class="mt-1 text-xs text-surface-500">تعداد: {{ item.quantity.toLocaleString('fa-IR') }}</div>
                                </div>
                                <div class="font-bold text-surface-900">{{ formatPrice(item.total_price) }}</div>
                            </div>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between"><dt class="text-surface-500">جمع کالاها</dt><dd class="font-bold">{{ formatPrice(subtotal) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-surface-500">ارسال</dt><dd class="font-bold">{{ shippingCost ? formatPrice(shippingCost) : 'رایگان' }}</dd></div>
                            <div class="flex justify-between border-t border-surface-200 pt-3 text-lg font-black"><dt>مبلغ نهایی</dt><dd>{{ formatPrice(total) }}</dd></div>
                        </dl>
                        <p v-if="form.errors.cart" class="mt-3 text-sm text-red-600">{{ form.errors.cart }}</p>
                        <button type="submit" class="mt-5 w-full rounded-lg bg-[#D4A017] px-4 py-3 font-black text-black transition hover:bg-[#c29212]" :disabled="form.processing">
                            {{ form.processing ? 'در حال ثبت سفارش...' : 'ثبت سفارش' }}
                        </button>
                    </div>
                </aside>
            </form>
        </main>
    </FrontLayout>
</template>
