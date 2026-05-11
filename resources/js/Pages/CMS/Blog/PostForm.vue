<script setup>
import RichTextEditor from '@/Components/CMS/RichTextEditor.vue';
import SeoFields from '@/Components/CMS/SeoFields.vue';
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import ImageUploader from '@/Components/ImageUploader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    post: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] }
});

const isEdit = computed(() => Boolean(props.post));
const form = useForm({
    _method: isEdit.value ? 'put' : 'post',
    title: props.post?.title ?? '',
    slug: props.post?.slug ?? '',
    excerpt: props.post?.excerpt ?? '',
    content: props.post?.content ?? '',
    featured_image: null,
    remove_featured_image: false,
    status: props.post?.status ?? 'draft',
    published_at: props.post?.published_at ?? '',
    post_category_id: props.post?.post_category_id ?? null,
    tag_ids: props.post?.tag_ids ?? [],
    product_ids: props.post?.product_ids ?? [],
    meta_title: props.post?.meta_title ?? '',
    meta_description: props.post?.meta_description ?? '',
    meta_keywords: props.post?.meta_keywords ?? [],
    canonical_url: props.post?.canonical_url ?? '',
    seo_index: props.post?.seo_index ?? true,
    seo_follow: props.post?.seo_follow ?? true
});

const submit = () => {
    const url = isEdit.value ? `/posts/${props.post.slug}` : '/posts';
    form.post(url, { forceFormData: true });
};

const currentFeaturedImage = computed(() => {
    if (!props.post?.featured_image_url || form.featured_image || form.remove_featured_image) {
        return [];
    }

    return [{ id: `post-featured-${props.post.id}`, url: props.post.featured_image_url, name: 'تصویر شاخص فعلی' }];
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
    <Head :title="isEdit ? `ویرایش ${post.title}` : 'ایجاد مقاله'" />
    <AppLayout>
        <TopNavTitle :title="isEdit ? `ویرایش ${post.title}` : 'ایجاد مقاله'" :breadcrumb="[{ label: 'مقالات', href: '/posts' }, { label: isEdit ? 'ویرایش' : 'ایجاد' }]">
            <template #pageAction>
                <Link href="/posts"><Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" outlined /></Link>
            </template>
        </TopNavTitle>
        <form class="space-y-4" @submit.prevent="submit">
            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">محتوای مقاله</h2>
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
                        <label class="mb-2 block font-medium">دسته‌بندی</label>
                        <Select v-model="form.post_category_id" :options="categories" optionLabel="name" optionValue="id" showClear class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">زمان انتشار</label>
                        <InputText v-model="form.published_at" placeholder="YYYY-MM-DD HH:mm:ss" class="w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">خلاصه</label>
                        <Textarea v-model="form.excerpt" rows="3" class="w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">متن مقاله</label>
                        <RichTextEditor v-model="form.content" :error="form.errors.content" />
                    </div>
                    <div class="md:col-span-2">
                        <ImageUploader
                            :modelValue="form.featured_image"
                            title="تصویر شاخص مقاله"
                            mode="single"
                            :existingImages="currentFeaturedImage"
                            :error="form.errors.featured_image"
                            @update:modelValue="setFeaturedImage"
                            @remove-existing="removeFeaturedImage"
                        />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">برچسب‌ها</label>
                        <MultiSelect v-model="form.tag_ids" :options="tags" optionLabel="name" optionValue="id" filter display="chip" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">محصولات مرتبط</label>
                        <MultiSelect v-model="form.product_ids" :options="products" optionLabel="name" optionValue="id" filter display="chip" class="w-full" />
                    </div>
                </div>
            </div>
            <SeoFields :form="form" basePath="/blog" :fallbackTitle="form.title || 'عنوان مقاله'" :fallbackDescription="form.excerpt || 'پیش‌نمایش خلاصه مقاله'" />
            <Message v-if="Object.keys(form.errors).length" severity="error">لطفاً خطاهای فرم را بررسی کنید.</Message>
            <div class="flex justify-end gap-2">
                <Link href="/posts"><Button type="button" label="انصراف" severity="secondary" text /></Link>
                <Button type="submit" label="ذخیره مقاله" icon="pi pi-check" :loading="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
