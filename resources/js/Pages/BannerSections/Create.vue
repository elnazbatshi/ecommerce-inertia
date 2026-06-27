<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    placements: { type: Array, default: () => [] },
    layouts: { type: Array, default: () => [] },
});

const form = useForm({
    title: '',
    key: '',
    placement: 'home_middle',
    layout: 'mixed_grid',
    sort_order: 0,
    is_active: true,
});

const submit = () => form.post('/admin/banner-sections', { preserveScroll: true });
</script>

<template>
    <Head title="ایجاد بخش بنر" />
    <AppLayout>
        <TopNavTitle title="ایجاد بخش بنر" :breadcrumb="[{ label: 'بخش‌های بنر', to: '/admin/banner-sections' }, { label: 'ایجاد' }]" />
        <Form :form="form" :placements="placements" :layouts="layouts" submitLabel="ذخیره بخش" @submit="submit" />
    </AppLayout>
</template>
