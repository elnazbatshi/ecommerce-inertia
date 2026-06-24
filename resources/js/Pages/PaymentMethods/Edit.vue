<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PaymentMethodForm from '@/Pages/PaymentMethods/Partials/PaymentMethodForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    paymentMethod: { type: Object, required: true }
});

console.log('paymentMethod prop:', props.paymentMethod);

const paymentMethod = computed(() => props.paymentMethod?.data ?? props.paymentMethod);
const numberOrNull = (value) => (value === null || value === undefined || value === '' ? null : Number(value));
const numberOrZero = (value) => Number(value ?? 0);
const booleanValue = (value) => value === true || value === 1 || value === '1' || value === 'true';

const form = useForm({
    _method: 'put',
    name: paymentMethod.value.name ?? '',
    slug: paymentMethod.value.slug ?? '',
    description: paymentMethod.value.description ?? '',
    driver: paymentMethod.value.driver ?? 'zarinpal',
    fee_type: paymentMethod.value.fee_type ?? 'none',
    fee_value: numberOrZero(paymentMethod.value.fee_value),
    min_amount: numberOrNull(paymentMethod.value.min_amount),
    max_amount: numberOrNull(paymentMethod.value.max_amount),
    settings: paymentMethod.value.settings ?? {},
    sort_order: numberOrZero(paymentMethod.value.sort_order),
    is_active: booleanValue(paymentMethod.value.is_active)
});

const save = () => {
    form.post(`/admin/payment-methods/${paymentMethod.value.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="ویرایش روش پرداخت" />
    <AppLayout>
        <TopNavTitle title="ویرایش روش پرداخت" :breadcrumb="[{ label: 'روش‌های پرداخت', to: '/admin/payment-methods' }, { label: 'ویرایش' }]" />
        <div class="card">
            <PaymentMethodForm
                :key="paymentMethod.id"
                :form="form"
                submitLabel="ذخیره تغییرات"
                :processing="form.processing"
                @submit="save"
            />
        </div>
    </AppLayout>
</template>
