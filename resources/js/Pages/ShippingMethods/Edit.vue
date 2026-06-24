<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ShippingMethodForm from '@/Pages/ShippingMethods/Partials/ShippingMethodForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    shippingMethod: { type: Object, required: true }
});

console.log('shippingMethod prop:', props.shippingMethod);

const shippingMethod = computed(() => props.shippingMethod?.data ?? props.shippingMethod);
const numberOrNull = (value) => (value === null || value === undefined || value === '' ? null : Number(value));
const numberOrZero = (value) => Number(value ?? 0);
const booleanValue = (value) => value === true || value === 1 || value === '1' || value === 'true';

const form = useForm({
    _method: 'put',
    name: shippingMethod.value.name ?? '',
    slug: shippingMethod.value.slug ?? '',
    description: shippingMethod.value.description ?? '',
    type: shippingMethod.value.type ?? 'fixed',
    base_cost: numberOrZero(shippingMethod.value.base_cost),
    free_from_amount: numberOrNull(shippingMethod.value.free_from_amount),
    min_order_amount: numberOrNull(shippingMethod.value.min_order_amount),
    max_order_amount: numberOrNull(shippingMethod.value.max_order_amount),
    estimated_delivery_days: shippingMethod.value.estimated_delivery_days ?? '',
    settings: shippingMethod.value.settings ?? {},
    sort_order: numberOrZero(shippingMethod.value.sort_order),
    is_active: booleanValue(shippingMethod.value.is_active)
});

const save = () => {
    form.post(`/admin/shipping-methods/${shippingMethod.value.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="ویرایش روش ارسال" />
    <AppLayout>
        <TopNavTitle title="ویرایش روش ارسال" :breadcrumb="[{ label: 'روش‌های ارسال', to: '/admin/shipping-methods' }, { label: 'ویرایش' }]" />
        <div class="card">
            <ShippingMethodForm
                :key="shippingMethod.id"
                :form="form"
                submitLabel="ذخیره تغییرات"
                :processing="form.processing"
                @submit="save"
            />
        </div>
    </AppLayout>
</template>
