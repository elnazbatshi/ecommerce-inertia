<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from '@/Pages/VehicleBrands/Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    vehicleTypeOptions: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    slug: '',
    vehicle_type_id: null,
    type: null,
    logo_media_id: null,
    country: '',
    description: '',
    sort_order: 0,
    is_active: true
});

const save = () => {
    form.post('/admin/vehicle-brands', { preserveScroll: true });
};
</script>

<template>
    <Head title="ایجاد برند خودرو" />
    <AppLayout>
        <TopNavTitle title="ایجاد برند خودرو" :breadcrumb="[{ label: 'برند خودرو', to: '/admin/vehicle-brands' }, { label: 'ایجاد' }]" />
        <div class="card">
            <Form :form="form" :vehicleTypeOptions="vehicleTypeOptions" submitLabel="ایجاد برند" :processing="form.processing" @submit="save" />
        </div>
    </AppLayout>
</template>
