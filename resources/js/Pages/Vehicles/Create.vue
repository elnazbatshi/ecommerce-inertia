<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from '@/Pages/Vehicles/Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    brandOptions: { type: Array, default: () => [] }
});

const form = useForm({
    vehicle_brand_id: null,
    type: 'car',
    name: '',
    slug: '',
    year_from: null,
    year_to: null,
    engine: '',
    trim: '',
    description: '',
    image_media_id: null,
    sort_order: 0,
    is_active: true
});

const typeOptions = [
    { label: 'خودرو', value: 'car' },
    { label: 'موتور سیکلت', value: 'motorcycle' }
];

const save = () => {
    form.post('/admin/vehicles', { preserveScroll: true });
};
</script>

<template>
    <Head title="ایجاد خودرو" />
    <AppLayout>
        <TopNavTitle title="ایجاد خودرو" :breadcrumb="[{ label: 'خودروها', to: '/admin/vehicles' }, { label: 'ایجاد' }]" />
        <div class="card">
            <Form
                :form="form"
                :brandOptions="brandOptions"
                :typeOptions="typeOptions"
                submitLabel="ایجاد خودرو"
                :processing="form.processing"
                @submit="save"
            />
        </div>
    </AppLayout>
</template>

