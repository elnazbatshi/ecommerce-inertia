<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    section: { type: Object, required: true },
    placements: { type: Array, default: () => [] },
    layouts: { type: Array, default: () => [] },
});

const form = useForm({
    _method: 'PUT',
    title: props.section.title ?? '',
    key: props.section.key ?? '',
    placement: props.section.placement ?? 'home_middle',
    layout: props.section.layout ?? 'mixed_grid',
    sort_order: props.section.sort_order ?? 0,
    is_active: Boolean(props.section.is_active),
});

const submit = () => form.post(`/admin/banner-sections/${props.section.id}`, { preserveScroll: true });
</script>

<template>
    <Head title="ویرایش بخش بنر" />
    <AppLayout>
        <TopNavTitle title="ویرایش بخش بنر" :breadcrumb="[{ label: 'بخش‌های بنر', to: '/admin/banner-sections' }, { label: 'ویرایش' }]" />
        <Form :form="form" :placements="placements" :layouts="layouts" submitLabel="ذخیره تغییرات" @submit="submit" />
    </AppLayout>
</template>
