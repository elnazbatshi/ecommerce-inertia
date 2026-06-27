<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from '@/Pages/VehicleTypes/Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    vehicleType: { type: Object, required: true },
});

const form = useForm({
    _method: 'PUT',
    name: props.vehicleType.name ?? '',
    slug: props.vehicleType.slug ?? '',
    description: props.vehicleType.description ?? '',
    sort_order: props.vehicleType.sort_order ?? 0,
    is_active: props.vehicleType.is_active ?? true,
});

const submit = () => form.post(`/admin/vehicle-types/${props.vehicleType.id}`, { preserveScroll: true });
</script>

<template>
    <Head title="ویرایش نوع وسیله" />
    <AppLayout>
        <TopNavTitle title="ویرایش نوع وسیله" :breadcrumb="[{ label: 'نوع وسیله', to: '/admin/vehicle-types' }, { label: 'ویرایش' }]" />
        <Form :form="form" submitLabel="ذخیره تغییرات" @submit="submit" />
    </AppLayout>
</template>
