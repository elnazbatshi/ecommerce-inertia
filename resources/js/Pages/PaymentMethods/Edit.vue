<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PaymentMethodForm from '@/Pages/PaymentMethods/Partials/PaymentMethodForm.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    paymentMethod: { type: Object, required: true }
});

const form = useForm({
    _method: 'put',
    name: props.paymentMethod.name ?? '',
    slug: props.paymentMethod.slug ?? '',
    description: props.paymentMethod.description ?? '',
    driver: props.paymentMethod.driver ?? 'zarinpal',
    fee_type: props.paymentMethod.fee_type ?? 'none',
    fee_value: props.paymentMethod.fee_value ?? 0,
    min_amount: props.paymentMethod.min_amount ?? null,
    max_amount: props.paymentMethod.max_amount ?? null,
    settings: props.paymentMethod.settings ?? {},
    sort_order: props.paymentMethod.sort_order ?? 0,
    is_active: props.paymentMethod.is_active ?? true
});

const save = () => {
    form.post(`/admin/payment-methods/${props.paymentMethod.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="ویرایش روش پرداخت" />
    <AppLayout>
        <TopNavTitle title="ویرایش روش پرداخت" :breadcrumb="[{ label: 'روش‌های پرداخت', to: '/admin/payment-methods' }, { label: 'ویرایش' }]" />
        <div class="card">
            <PaymentMethodForm :form="form" submitLabel="ذخیره تغییرات" :processing="form.processing" @submit="save" />
        </div>
    </AppLayout>
</template>
