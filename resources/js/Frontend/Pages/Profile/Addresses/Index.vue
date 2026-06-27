<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FrontLayout from '../../../Layouts/FrontLayout.vue';

const props = defineProps({
    customer: { type: Object, required: true },
    addresses: { type: Array, default: () => [] },
    provinces: { type: Array, default: () => [] },
});

const editingId = ref(null);

const emptyForm = {
    title: '',
    receiver_name: props.customer.name || '',
    receiver_phone: props.customer.phone || '',
    province_id: '',
    city_id: '',
    postal_code: '',
    address: '',
    plaque: '',
    unit: '',
    is_default: false,
};

const form = useForm({ ...emptyForm });

const isEditing = computed(() => editingId.value !== null);

const selectedProvince = computed(() => {
    const provinceId = Number(form.province_id || 0);

    return props.provinces.find((province) => Number(province.id) === provinceId) || null;
});

const availableCities = computed(() => selectedProvince.value?.cities || []);

watch(() => form.province_id, () => {
    if (!availableCities.value.some((city) => Number(city.id) === Number(form.city_id))) {
        form.city_id = '';
    }
});

const resetForm = () => {
    editingId.value = null;
    form.clearErrors();
    form.defaults({ ...emptyForm });
    form.reset();
};

const editAddress = (address) => {
    editingId.value = address.id;
    form.clearErrors();
    form.title = address.title || '';
    form.receiver_name = address.receiver_name || '';
    form.receiver_phone = address.receiver_phone || '';
    form.province_id = address.province_id || '';
    form.city_id = address.city_id || '';
    form.postal_code = address.postal_code || '';
    form.address = address.address || '';
    form.plaque = address.plaque || '';
    form.unit = address.unit || '';
    form.is_default = !!address.is_default;
};

const submitAddress = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    };

    if (isEditing.value) {
        form.put(`/profile/addresses/${editingId.value}`, options);
        return;
    }

    form.post('/profile/addresses', options);
};

const deleteAddress = (address) => {
    if (!window.confirm('این آدرس حذف شود؟')) return;

    router.delete(`/profile/addresses/${address.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingId.value === address.id) resetForm();
        },
    });
};

const setDefaultAddress = (address) => {
    router.patch(`/profile/addresses/${address.id}/default`, {}, {
        preserveScroll: true,
    });
};

const logoutCustomer = () => {
    router.post('/customer/logout', {}, {
        preserveScroll: true,
        onSuccess: () => router.visit('/'),
    });
};
</script>

<template>
    <Head>
        <title>آدرس‌های من | MotoPart</title>
    </Head>

    <FrontLayout>
        <main class="mx-auto max-w-7xl px-4 py-8 md:px-6" dir="rtl">
            <nav class="mb-5 text-sm text-surface-500">
                <Link href="/" class="hover:text-surface-800">خانه</Link>
                <i class="pi pi-angle-left mx-2 text-xs" />
                <span class="text-surface-900">آدرس‌های من</span>
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
                            href="/profile/addresses"
                            class="flex items-center justify-between border-r-4 border-[#D4A017] border-t border-surface-100 bg-[#D4A017]/10 px-4 py-4 text-sm font-black text-surface-950"
                        >
                            <span>آدرس‌ها</span>
                            <i class="pi pi-map-marker text-[#D4A017]"></i>
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

                <section class="min-w-0 space-y-5">
                    <section class="rounded-lg border border-surface-200 bg-white shadow-sm">
                        <div class="flex flex-col gap-2 border-b border-surface-100 px-5 py-5 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h1 class="text-2xl font-black text-surface-950">آدرس‌های من</h1>
                                <p class="mt-1 text-sm text-surface-500">آدرس‌های ارسال سفارش‌های خود را مدیریت کنید.</p>
                            </div>
                            <span class="text-sm font-bold text-surface-500">
                                {{ Number(props.addresses.length).toLocaleString('fa-IR') }} آدرس
                            </span>
                        </div>

                        <div class="p-5">
                            <form class="grid gap-4 rounded-lg bg-surface-50 p-4 md:grid-cols-2" @submit.prevent="submitAddress">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-surface-700">عنوان آدرس</label>
                                    <input v-model="form.title" type="text" class="w-full rounded-lg border border-surface-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#D4A017]" placeholder="خانه، محل کار..." />
                                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-surface-700">نام گیرنده</label>
                                    <input v-model="form.receiver_name" type="text" class="w-full rounded-lg border border-surface-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#D4A017]" />
                                    <p v-if="form.errors.receiver_name" class="mt-1 text-xs text-red-600">{{ form.errors.receiver_name }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-surface-700">موبایل گیرنده</label>
                                    <input v-model="form.receiver_phone" type="text" class="w-full rounded-lg border border-surface-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#D4A017]" />
                                    <p v-if="form.errors.receiver_phone" class="mt-1 text-xs text-red-600">{{ form.errors.receiver_phone }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-surface-700">کد پستی</label>
                                    <input v-model="form.postal_code" type="text" class="w-full rounded-lg border border-surface-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#D4A017]" />
                                    <p v-if="form.errors.postal_code" class="mt-1 text-xs text-red-600">{{ form.errors.postal_code }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-surface-700">استان</label>
                                    <select
                                        v-model="form.province_id"
                                        class="w-full rounded-lg border border-surface-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#D4A017]"
                                    >
                                        <option value="">انتخاب استان</option>
                                        <option v-for="province in props.provinces" :key="province.id" :value="province.id">
                                            {{ province.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.province_id" class="mt-1 text-xs text-red-600">{{ form.errors.province_id }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-surface-700">شهر</label>
                                    <select
                                        v-model="form.city_id"
                                        class="w-full rounded-lg border border-surface-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#D4A017]"
                                        :disabled="!form.province_id"
                                    >
                                        <option value="">{{ form.province_id ? 'انتخاب شهر' : 'اول استان را انتخاب کنید' }}</option>
                                        <option v-for="city in availableCities" :key="city.id" :value="city.id">
                                            {{ city.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.city_id" class="mt-1 text-xs text-red-600">{{ form.errors.city_id }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-surface-700">پلاک</label>
                                    <input v-model="form.plaque" type="text" class="w-full rounded-lg border border-surface-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#D4A017]" />
                                    <p v-if="form.errors.plaque" class="mt-1 text-xs text-red-600">{{ form.errors.plaque }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-surface-700">واحد</label>
                                    <input v-model="form.unit" type="text" class="w-full rounded-lg border border-surface-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#D4A017]" />
                                    <p v-if="form.errors.unit" class="mt-1 text-xs text-red-600">{{ form.errors.unit }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-bold text-surface-700">نشانی کامل</label>
                                    <textarea v-model="form.address" rows="3" class="w-full rounded-lg border border-surface-200 bg-white px-3 py-3 text-sm outline-none focus:border-[#D4A017]"></textarea>
                                    <p v-if="form.errors.address" class="mt-1 text-xs text-red-600">{{ form.errors.address }}</p>
                                </div>

                                <label class="flex items-center gap-2 text-sm font-bold text-surface-700 md:col-span-2">
                                    <input v-model="form.is_default" type="checkbox" class="h-4 w-4 rounded border-surface-300 accent-[#D4A017]" />
                                    <span>آدرس پیش‌فرض باشد</span>
                                </label>

                                <div class="flex flex-col gap-2 md:col-span-2 md:flex-row md:justify-end">
                                    <button
                                        v-if="isEditing"
                                        type="button"
                                        class="rounded-lg border border-surface-200 px-5 py-3 text-sm font-bold text-surface-700 hover:bg-white"
                                        @click="resetForm"
                                    >
                                        انصراف
                                    </button>
                                    <button
                                        type="submit"
                                        class="rounded-lg bg-[#D4A017] px-5 py-3 text-sm font-black text-black hover:bg-[#c49412] disabled:opacity-60"
                                        :disabled="form.processing"
                                    >
                                        {{ isEditing ? 'ذخیره تغییرات' : 'ثبت آدرس جدید' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="rounded-lg border border-surface-200 bg-white p-5 shadow-sm">
                        <div v-if="!props.addresses.length" class="rounded-lg border border-dashed border-surface-300 bg-surface-50 px-4 py-14 text-center">
                            <i class="pi pi-map-marker mb-4 block text-4xl text-[#D4A017]" />
                            <h2 class="text-lg font-black text-surface-950">هنوز آدرسی ثبت نکرده‌اید.</h2>
                            <p class="mt-2 text-sm text-surface-500">از فرم بالا اولین آدرس ارسال خود را اضافه کنید.</p>
                        </div>

                        <div v-else class="space-y-4">
                            <article
                                v-for="address in props.addresses"
                                :key="address.id"
                                class="rounded-lg border border-surface-200 p-4"
                            >
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-black text-surface-950">{{ address.title || 'آدرس' }}</h3>
                                            <span v-if="address.is_default" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">
                                                پیش‌فرض
                                            </span>
                                        </div>
                                        <p class="mt-3 text-sm leading-7 text-surface-700">
                                            {{ address.province }}، {{ address.city }}، {{ address.address }}
                                        </p>
                                        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-surface-500">
                                            <span>گیرنده: {{ address.receiver_name }}</span>
                                            <span>موبایل: {{ address.receiver_phone }}</span>
                                            <span v-if="address.postal_code">کد پستی: {{ address.postal_code }}</span>
                                            <span v-if="address.plaque">پلاک: {{ address.plaque }}</span>
                                            <span v-if="address.unit">واحد: {{ address.unit }}</span>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        <button
                                            v-if="!address.is_default"
                                            type="button"
                                            class="rounded-lg border border-surface-200 px-3 py-2 text-xs font-bold text-surface-700 hover:border-[#D4A017] hover:text-[#D4A017]"
                                            @click="setDefaultAddress(address)"
                                        >
                                            پیش‌فرض
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg border border-surface-200 px-3 py-2 text-xs font-bold text-surface-700 hover:border-[#D4A017] hover:text-[#D4A017]"
                                            @click="editAddress(address)"
                                        >
                                            ویرایش
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg border border-red-100 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50"
                                            @click="deleteAddress(address)"
                                        >
                                            حذف
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>
                </section>
            </div>
        </main>
    </FrontLayout>
</template>
