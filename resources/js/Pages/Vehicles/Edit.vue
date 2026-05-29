<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from '@/Pages/Vehicles/Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    vehicle: { type: Object, required: true },
    brandOptions: { type: Array, default: () => [] }
});

const form = useForm({
    _method: 'put',
    vehicle_brand_id: props.vehicle.vehicle_brand_id ?? null,
    type: props.vehicle.type ?? 'car',
    name: props.vehicle.name ?? '',
    slug: props.vehicle.slug ?? '',
    year_from: props.vehicle.year_from ?? null,
    year_to: props.vehicle.year_to ?? null,
    engine: props.vehicle.engine ?? '',
    trim: props.vehicle.trim ?? '',
    description: props.vehicle.description ?? '',
    image_media_id: props.vehicle.image_media_id ?? null,
    sort_order: props.vehicle.sort_order ?? 0,
    is_active: props.vehicle.is_active ?? true
});

const typeOptions = [
    { label: 'خودرو', value: 'car' },
    { label: 'موتور سیکلت', value: 'motorcycle' }
];

const save = () => {
    form.post(`/admin/vehicles/${props.vehicle.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="ویرایش خودرو" />
    <AppLayout>
        <TopNavTitle title="ویرایش خودرو" :breadcrumb="[{ label: 'خودروها', to: '/admin/vehicles' }, { label: 'ویرایش' }]" />
        <div class="card">
            <Form
                :form="form"
                :brandOptions="brandOptions"
                :typeOptions="typeOptions"
                :initialImage="vehicle.image_media"
                submitLabel="ذخیره تغییرات"
                :processing="form.processing"
                @submit="save"
            />
        </div>
    </AppLayout>
</template>

