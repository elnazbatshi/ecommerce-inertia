<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ShippingMethodForm from '@/Pages/ShippingMethods/Partials/ShippingMethodForm.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    slug: '',
    description: '',
    type: 'fixed',
    base_cost: 0,
    free_from_amount: null,
    min_order_amount: null,
    max_order_amount: null,
    estimated_delivery_days: '',
    settings: {},
    sort_order: 0,
    is_active: true
});

const save = () => {
    form.post('/admin/shipping-methods', { preserveScroll: true });
};
</script>

<template>
    <Head title="ایجاد روش ارسال" />
    <AppLayout>
        <TopNavTitle title="ایجاد روش ارسال" :breadcrumb="[{ label: 'روش‌های ارسال', to: '/admin/shipping-methods' }, { label: 'ایجاد' }]" />
        <div class="card">
            <ShippingMethodForm :form="form" submitLabel="ایجاد روش ارسال" :processing="form.processing" @submit="save" />
        </div>
    </AppLayout>
</template>
