<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PaymentMethodForm from '@/Pages/PaymentMethods/Partials/PaymentMethodForm.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    slug: '',
    description: '',
    driver: 'zarinpal',
    fee_type: 'none',
    fee_value: 0,
    min_amount: null,
    max_amount: null,
    settings: {},
    sort_order: 0,
    is_active: true
});

const save = () => {
    form.post('/admin/payment-methods', { preserveScroll: true });
};
</script>

<template>
    <Head title="ایجاد روش پرداخت" />
    <AppLayout>
        <TopNavTitle title="ایجاد روش پرداخت" :breadcrumb="[{ label: 'روش‌های پرداخت', to: '/admin/payment-methods' }, { label: 'ایجاد' }]" />
        <div class="card">
            <PaymentMethodForm :form="form" submitLabel="ایجاد روش پرداخت" :processing="form.processing" @submit="save" />
        </div>
    </AppLayout>
</template>
