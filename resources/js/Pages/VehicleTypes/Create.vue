<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from '@/Pages/VehicleTypes/Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    slug: '',
    description: '',
    sort_order: 0,
    is_active: true,
});

const submit = () => form.post('/admin/vehicle-types', { preserveScroll: true });
</script>

<template>
    <Head title="ایجاد نوع وسیله" />
    <AppLayout>
        <TopNavTitle title="ایجاد نوع وسیله" :breadcrumb="[{ label: 'نوع وسیله', to: '/admin/vehicle-types' }, { label: 'ایجاد' }]" />
        <Form :form="form" submitLabel="ذخیره نوع وسیله" @submit="submit" />
    </AppLayout>
</template>
