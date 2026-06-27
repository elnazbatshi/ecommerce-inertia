<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from '@/Pages/VehicleBrands/Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    brand: { type: Object, required: true },
    vehicleTypeOptions: { type: Array, default: () => [] },
});

const form = useForm({
    _method: 'put',
    name: props.brand.name ?? '',
    slug: props.brand.slug ?? '',
    vehicle_type_id: props.brand.vehicle_type_id ?? null,
    type: props.brand.type ?? null,
    logo_media_id: props.brand.logo_media_id ?? null,
    country: props.brand.country ?? '',
    description: props.brand.description ?? '',
    sort_order: props.brand.sort_order ?? 0,
    is_active: props.brand.is_active ?? true
});

const save = () => {
    form.post(`/admin/vehicle-brands/${props.brand.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="ویرایش برند خودرو" />
    <AppLayout>
        <TopNavTitle title="ویرایش برند خودرو" :breadcrumb="[{ label: 'برند خودرو', to: '/admin/vehicle-brands' }, { label: 'ویرایش' }]" />
        <div class="card">
            <Form
                :form="form"
                :vehicleTypeOptions="vehicleTypeOptions"
                :initialLogo="brand.logo_media"
                submitLabel="ذخیره تغییرات"
                :processing="form.processing"
                @submit="save"
            />
        </div>
    </AppLayout>
</template>
