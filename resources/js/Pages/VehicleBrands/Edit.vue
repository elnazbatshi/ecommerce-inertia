<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from '@/Pages/VehicleBrands/Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    brand: { type: Object, required: true }
});

const form = useForm({
    _method: 'put',
    name: props.brand.name ?? '',
    slug: props.brand.slug ?? '',
    type: props.brand.type ?? 'car',
    logo_media_id: props.brand.logo_media_id ?? null,
    country: props.brand.country ?? '',
    description: props.brand.description ?? '',
    sort_order: props.brand.sort_order ?? 0,
    is_active: props.brand.is_active ?? true
});

const typeOptions = [
    { label: 'خودرو', value: 'car' },
    { label: 'موتور سیکلت', value: 'motorcycle' },
    { label: 'عمومی', value: 'universal' }
];

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
                :typeOptions="typeOptions"
                :initialLogo="brand.logo_media"
                submitLabel="ذخیره تغییرات"
                :processing="form.processing"
                @submit="save"
            />
        </div>
    </AppLayout>
</template>

