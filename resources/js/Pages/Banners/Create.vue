<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    section: { type: Object, required: true },
});

const form = useForm({
    title: '',
    subtitle: '',
    description: '',
    image_media_id: null,
    mobile_image_media_id: null,
    image_media: null,
    mobile_image_media: null,
    link_url: '',
    button_text: '',
    background_color: '#111111',
    text_color: '#ffffff',
    sort_order: 0,
    is_active: true,
    starts_at: '',
    ends_at: '',
});

const submit = () => form.post(`/admin/banner-sections/${props.section.id}/banners`, { preserveScroll: true });
</script>

<template>
    <Head title="ایجاد بنر" />
    <AppLayout>
        <TopNavTitle title="ایجاد بنر" :breadcrumb="[{ label: 'بخش‌های بنر', to: '/admin/banner-sections' }, { label: section.title, to: `/admin/banner-sections/${section.id}/banners` }, { label: 'ایجاد' }]" />
        <Form :form="form" submitLabel="ذخیره بنر" @submit="submit" />
    </AppLayout>
</template>
