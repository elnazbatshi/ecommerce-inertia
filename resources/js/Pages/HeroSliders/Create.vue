<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import HeroSliderForm from './Partials/HeroSliderForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    layouts: { type: Array, default: () => [] },
    placements: { type: Array, default: () => [] }
});

const form = useForm({
    title: '',
    subtitle: '',
    eyebrow_text: '',
    description: '',
    background_media_id: null,
    foreground_media_id: null,
    background_media: null,
    foreground_media: null,
    overlay_opacity: 0.55,
    show_overlay_content: true,
    button_primary_text: '',
    button_primary_url: '',
    button_secondary_text: '',
    button_secondary_url: '',
    badge_text: '',
    badge_url: '',
    stat_1_label: '',
    stat_1_value: '',
    stat_2_label: '',
    stat_2_value: '',
    stat_3_label: '',
    stat_3_value: '',
    text_color: '#ffffff',
    accent_color: '#D4A017',
    button_color: '#D4A017',
    layout: 'image_left_content_right',
    placement: 'hero',
    sort_order: 0,
    is_active: true,
    starts_at: '',
    ends_at: ''
});

const submit = () => {
    form.post('/admin/hero-sliders');
};
</script>

<template>
    <Head title="اسلایدر جدید" />

    <AppLayout>
        <TopNavTitle title="اسلایدر جدید" :breadcrumb="[{ label: 'اسلایدر صفحه اصلی', href: '/admin/hero-sliders' }, { label: 'ایجاد' }]">
            <template #pageAction>
                <Link href="/admin/hero-sliders">
                    <Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <HeroSliderForm :form="form" :layouts="layouts" :placements="placements" submitLabel="ذخیره اسلایدر" :processing="form.processing" @submit="submit" />
    </AppLayout>
</template>
