<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from '@/Pages/VehicleBrands/Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    slug: '',
    type: 'car',
    logo_media_id: null,
    country: '',
    description: '',
    sort_order: 0,
    is_active: true
});

const typeOptions = [
    { label: 'خودرو', value: 'car' },
    { label: 'موتور سیکلت', value: 'motorcycle' },
    { label: 'عمومی', value: 'universal' }
];

const save = () => {
    form.post('/admin/vehicle-brands', { preserveScroll: true });
};
</script>

<template>
    <Head title="ایجاد برند خودرو" />
    <AppLayout>
        <TopNavTitle title="ایجاد برند خودرو" :breadcrumb="[{ label: 'برند خودرو', to: '/admin/vehicle-brands' }, { label: 'ایجاد' }]" />
        <div class="card">
            <Form :form="form" :typeOptions="typeOptions" submitLabel="ایجاد برند" :processing="form.processing" @submit="save" />
        </div>
    </AppLayout>
</template>

