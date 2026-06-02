<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import HeroSliderForm from './Partials/HeroSliderForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    slider: { type: Object, required: true },
    layouts: { type: Array, default: () => [] }
});

const form = useForm({
    title: props.slider.title ?? '',
    subtitle: props.slider.subtitle ?? '',
    eyebrow_text: props.slider.eyebrow_text ?? '',
    description: props.slider.description ?? '',
    background_media_id: props.slider.background_media_id ?? null,
    foreground_media_id: props.slider.foreground_media_id ?? null,
    background_media: props.slider.background_media ?? null,
    foreground_media: props.slider.foreground_media ?? null,
    overlay_opacity: Number(props.slider.overlay_opacity ?? 0.55),
    button_primary_text: props.slider.button_primary_text ?? '',
    button_primary_url: props.slider.button_primary_url ?? '',
    button_secondary_text: props.slider.button_secondary_text ?? '',
    button_secondary_url: props.slider.button_secondary_url ?? '',
    badge_text: props.slider.badge_text ?? '',
    badge_url: props.slider.badge_url ?? '',
    stat_1_label: props.slider.stat_1_label ?? '',
    stat_1_value: props.slider.stat_1_value ?? '',
    stat_2_label: props.slider.stat_2_label ?? '',
    stat_2_value: props.slider.stat_2_value ?? '',
    stat_3_label: props.slider.stat_3_label ?? '',
    stat_3_value: props.slider.stat_3_value ?? '',
    text_color: props.slider.text_color ?? '#ffffff',
    accent_color: props.slider.accent_color ?? '#D4A017',
    button_color: props.slider.button_color ?? '#D4A017',
    layout: props.slider.layout ?? 'image_left_content_right',
    sort_order: Number(props.slider.sort_order ?? 0),
    is_active: Boolean(props.slider.is_active),
    starts_at: props.slider.starts_at ?? '',
    ends_at: props.slider.ends_at ?? ''
});

const submit = () => {
    form.put(`/admin/hero-sliders/${props.slider.id}`);
};
</script>

<template>
    <Head :title="`ویرایش ${slider.title}`" />

    <AppLayout>
        <TopNavTitle :title="`ویرایش ${slider.title}`" :breadcrumb="[{ label: 'اسلایدر صفحه اصلی', href: '/admin/hero-sliders' }, { label: 'ویرایش' }]">
            <template #pageAction>
                <Link href="/admin/hero-sliders">
                    <Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <HeroSliderForm :form="form" :layouts="layouts" submitLabel="ذخیره تغییرات" :processing="form.processing" @submit="submit" />
    </AppLayout>
</template>
