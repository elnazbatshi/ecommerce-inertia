<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    section: { type: Object, required: true },
    banner: { type: Object, required: true },
});

const form = useForm({
    _method: 'PUT',
    title: props.banner.title ?? '',
    subtitle: props.banner.subtitle ?? '',
    description: props.banner.description ?? '',
    image_media_id: props.banner.image_media_id ?? null,
    mobile_image_media_id: props.banner.mobile_image_media_id ?? null,
    image_media: props.banner.image_media ?? null,
    mobile_image_media: props.banner.mobile_image_media ?? null,
    link_url: props.banner.link_url ?? '',
    button_text: props.banner.button_text ?? '',
    background_color: props.banner.background_color ?? '#111111',
    text_color: props.banner.text_color ?? '#ffffff',
    sort_order: props.banner.sort_order ?? 0,
    is_active: Boolean(props.banner.is_active),
    starts_at: props.banner.starts_at ?? '',
    ends_at: props.banner.ends_at ?? '',
});

const submit = () => form.post(`/admin/banners/${props.banner.id}`, { preserveScroll: true });
</script>

<template>
    <Head title="ویرایش بنر" />
    <AppLayout>
        <TopNavTitle title="ویرایش بنر" :breadcrumb="[{ label: 'بخش‌های بنر', to: '/admin/banner-sections' }, { label: section.title, to: `/admin/banner-sections/${section.id}/banners` }, { label: 'ویرایش' }]" />
        <Form :form="form" submitLabel="ذخیره تغییرات" @submit="submit" />
    </AppLayout>
</template>
