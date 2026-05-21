<script setup>
import RichTextEditor from '@/Components/CMS/RichTextEditor.vue';
import SeoFields from '@/Components/CMS/SeoFields.vue';
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import PersianDateTimePicker from '@/Components/Date/PersianDateTimePicker.vue';
import ImageUploader from '@/Components/ImageUploader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    page: { type: Object, default: null },
    statusOptions: { type: Array, default: () => [] },
    templateOptions: { type: Array, default: () => [] }
});

const isEdit = computed(() => Boolean(props.page));
const form = useForm({
    _method: isEdit.value ? 'put' : 'post',
    title: props.page?.title ?? '',
    slug: props.page?.slug ?? '',
    content: props.page?.content ?? '',
    featured_image: null,
    remove_featured_image: false,
    status: props.page?.status ?? 'draft',
    published_at: props.page?.published_at ?? '',
    template: props.page?.template ?? null,
    meta_title: props.page?.meta_title ?? '',
    meta_description: props.page?.meta_description ?? '',
    meta_keywords: props.page?.meta_keywords ?? [],
    canonical_url: props.page?.canonical_url ?? '',
    seo_index: props.page?.seo_index ?? true,
    seo_follow: props.page?.seo_follow ?? true
});

const submit = () => {
    const url = isEdit.value ? `/pages/${props.page.slug}` : '/pages';
    form.post(url, { forceFormData: true });
};

const currentFeaturedImage = computed(() => {
    if (!props.page?.featured_image_url || form.featured_image || form.remove_featured_image) {
        return [];
    }

    return [{ id: `page-featured-${props.page.id}`, url: props.page.featured_image_url, name: 'تصویر شاخص فعلی' }];
});

const setFeaturedImage = (file) => {
    form.featured_image = file;
    form.remove_featured_image = false;
};

const removeFeaturedImage = () => {
    form.featured_image = null;
    form.remove_featured_image = true;
};
</script>

<template>
    <Head :title="isEdit ? `ویرایش ${page.title}` : 'ایجاد صفحه'" />
    <AppLayout>
        <TopNavTitle :title="isEdit ? `ویرایش ${page.title}` : 'ایجاد صفحه'" :breadcrumb="[{ label: 'صفحات', href: '/pages' }, { label: isEdit ? 'ویرایش' : 'ایجاد' }]">
            <template #pageAction><Link href="/pages"><Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" outlined /></Link></template>
        </TopNavTitle>
        <form class="space-y-4" @submit.prevent="submit">
            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">محتوای صفحه</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block font-medium">عنوان</label>
                        <InputText v-model="form.title" class="w-full" />
                        <small v-if="form.errors.title" class="text-red-600">{{ form.errors.title }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">وضعیت</label>
                        <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">قالب</label>
                        <Select v-model="form.template" :options="templateOptions" optionLabel="label" optionValue="value" showClear class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">زمان انتشار</label>
                        <PersianDateTimePicker v-model="form.published_at" :invalid="Boolean(form.errors.published_at)" />
                        <small v-if="form.errors.published_at" class="text-red-600">{{ form.errors.published_at }}</small>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">متن صفحه</label>
                        <RichTextEditor v-model="form.content" :error="form.errors.content" />
                    </div>
                    <div class="md:col-span-2">
                        <ImageUploader
                            :modelValue="form.featured_image"
                            title="تصویر شاخص صفحه"
                            mode="single"
                            :existingImages="currentFeaturedImage"
                            :error="form.errors.featured_image"
                            @update:modelValue="setFeaturedImage"
                            @remove-existing="removeFeaturedImage"
                        />
                    </div>
                </div>
            </div>
            <SeoFields :form="form" basePath="/page" :fallbackTitle="form.title || 'عنوان صفحه'" fallbackDescription="پیش‌نمایش توضیحات صفحه ثابت" />
            <Message v-if="Object.keys(form.errors).length" severity="error">لطفاً خطاهای فرم را بررسی کنید.</Message>
            <div class="flex justify-end gap-2">
                <Link href="/pages"><Button type="button" label="انصراف" severity="secondary" text /></Link>
                <Button type="submit" label="ذخیره صفحه" icon="pi pi-check" :loading="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
