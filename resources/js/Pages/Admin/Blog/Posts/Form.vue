<script setup>
import RichTextEditor from '@/Components/CMS/RichTextEditor.vue';
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
    statuses: { type: Array, default: () => ['draft', 'published', 'archived'] },
});

const isEdit = computed(() => Boolean(props.post));
const statusLabels = {
    draft: 'پیش‌نویس',
    published: 'منتشرشده',
    archived: 'آرشیوشده',
};
const statusOptions = computed(() => props.statuses.map((status) => ({
    label: statusLabels[status] || status,
    value: status,
})));
const form = useForm({
    _method: isEdit.value ? 'put' : 'post',
    title: props.post?.title ?? '',
    slug: props.post?.slug ?? '',
    excerpt: props.post?.excerpt ?? '',
    content: props.post?.content ?? '',
    featured_image: null,
    featured_image_alt: props.post?.featured_image_alt ?? '',
    blog_category_id: props.post?.category?.id ?? null,
    tag_ids: props.post?.tag_ids ?? [],
    product_ids: props.post?.product_ids ?? [],
    meta_title: props.post?.meta_title ?? '',
    meta_description: props.post?.meta_description ?? '',
    canonical_url: props.post?.canonical_url ?? '',
    status: props.post?.status ?? 'draft',
    published_at: props.post?.published_at ?? '',
    is_featured: Boolean(props.post?.is_featured ?? false),
});

const currentFeaturedImage = computed(() => {
    if (!props.post?.featured_image_url || form.featured_image) return [];
    return [{ id: `blog-post-${props.post.id}`, url: props.post.featured_image_url, name: 'تصویر شاخص فعلی' }];
});
const selectedProducts = computed(() => props.products.filter((product) => form.product_ids.includes(product.id)));
const seoTitle = computed(() => form.meta_title || form.title || 'عنوان مقاله بلاگ');
const seoDescription = computed(() => form.meta_description || form.excerpt || 'پیش‌نمایش خلاصه مقاله');
const seoUrl = computed(() => `${window.location.origin}/blog/${form.slug || props.post?.slug || 'post-slug'}`);

const setFeaturedImage = (file) => {
    form.featured_image = file;
};

const removeFeaturedImage = () => {
    form.featured_image = null;
};

const validateBeforeSubmit = () => {
    form.clearErrors('title', 'content', 'status');
    const errors = {};
    if (!form.title) errors.title = 'وارد کردن عنوان الزامی است.';
    if (!form.content) errors.content = 'وارد کردن محتوا الزامی است.';
    if (!form.status) errors.status = 'انتخاب وضعیت الزامی است.';

    if (Object.keys(errors).length) {
        form.setError(errors);
        return false;
    }

    return true;
};

const submit = () => {
    if (!validateBeforeSubmit()) return;
    const url = isEdit.value ? `/admin/blog-posts/${props.post.slug}` : '/admin/blog-posts';
    form.post(url, { forceFormData: true });
};
</script>

<template>
    <Head :title="isEdit ? `ویرایش ${post.title}` : 'ایجاد مقاله بلاگ'" />

    <AppLayout>
        <TopNavTitle :title="isEdit ? `ویرایش ${post.title}` : 'ایجاد مقاله بلاگ'" :breadcrumb="[{ label: 'مقالات بلاگ', href: '/admin/blog-posts' }, { label: isEdit ? 'ویرایش' : 'ایجاد' }]">
            <template #pageAction>
                <Link href="/admin/blog-posts">
                    <Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">اطلاعات اصلی</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block font-medium">عنوان</label>
                        <InputText v-model="form.title" class="w-full" :invalid="Boolean(form.errors.title)" />
                        <small v-if="form.errors.title" class="text-red-600">{{ form.errors.title }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">اسلاگ</label>
                        <InputText v-model="form.slug" class="w-full" />
                        <small v-if="form.errors.slug" class="text-red-600">{{ form.errors.slug }}</small>
                    </div>
                    <div class="md:col-span-2">
                        <ImageUploader
                            :modelValue="form.featured_image"
                            title="تصویر شاخص"
                            mode="single"
                            :existingImages="currentFeaturedImage"
                            :error="form.errors.featured_image"
                            @update:modelValue="setFeaturedImage"
                            @remove-existing="removeFeaturedImage"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">متن جایگزین تصویر</label>
                        <InputText v-model="form.featured_image_alt" class="w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">خلاصه</label>
                        <Textarea v-model="form.excerpt" rows="3" class="w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">محتوا</label>
                        <RichTextEditor v-model="form.content" :error="form.errors.content" allowMediaBrowser />
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">دسته‌بندی، تگ و محصولات</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block font-medium">دسته‌بندی</label>
                        <Select v-model="form.blog_category_id" :options="categories" optionLabel="name" optionValue="id" showClear class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">تگ‌ها</label>
                        <MultiSelect v-model="form.tag_ids" :options="tags" optionLabel="name" optionValue="id" filter display="chip" class="w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">محصولات مرتبط</label>
                        <MultiSelect v-model="form.product_ids" :options="products" optionLabel="name" optionValue="id" filter display="chip" class="w-full">
                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <img v-if="option.image_url" :src="option.image_url" class="h-8 w-8 rounded object-cover" :alt="option.name" />
                                    <span v-else class="flex h-8 w-8 items-center justify-center rounded bg-surface-100 text-surface-400"><i class="pi pi-image" /></span>
                                    <span>{{ option.name }}</span>
                                </div>
                            </template>
                        </MultiSelect>
                        <div v-if="selectedProducts.length" class="mt-3 flex flex-wrap gap-2">
                            <Tag v-for="product in selectedProducts" :key="product.id" :value="product.name" severity="info" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">SEO</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <div class="mb-1 flex justify-between">
                            <label class="font-medium">عنوان متا</label>
                            <small :class="form.meta_title.length > 60 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_title.length }}/60</small>
                        </div>
                        <InputText v-model="form.meta_title" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">آدرس canonical</label>
                        <InputText v-model="form.canonical_url" class="w-full" />
                        <small v-if="form.errors.canonical_url" class="text-red-600">{{ form.errors.canonical_url }}</small>
                    </div>
                    <div class="md:col-span-2">
                        <div class="mb-1 flex justify-between">
                            <label class="font-medium">توضیحات متا</label>
                            <small :class="form.meta_description.length > 160 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_description.length }}/160</small>
                        </div>
                        <Textarea v-model="form.meta_description" rows="3" class="w-full" />
                    </div>
                </div>
                <div class="mt-5 rounded-md border border-surface-200 p-4">
                    <div class="text-lg text-blue-700">{{ seoTitle }}</div>
                    <div class="text-sm text-green-700">{{ seoUrl }}</div>
                    <p class="mt-1 text-sm text-surface-600">{{ seoDescription }}</p>
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">انتشار</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block font-medium">وضعیت</label>
                        <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                        <small v-if="form.errors.status" class="text-red-600">{{ form.errors.status }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">زمان انتشار</label>
                        <DatePicker v-model="form.published_at" showTime hourFormat="24" dateFormat="yy-mm-dd" class="w-full" />
                        <small v-if="form.errors.published_at" class="text-red-600">{{ form.errors.published_at }}</small>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-surface-200 p-3">
                        <ToggleSwitch v-model="form.is_featured" />
                        <span class="font-medium">ویژه</span>
                    </div>
                </div>
            </div>

            <Message v-if="Object.keys(form.errors).length" severity="error">
                لطفاً خطاهای فرم را بررسی کن.
            </Message>

            <div class="flex justify-end gap-2">
                <Link href="/admin/blog-posts">
                    <Button type="button" label="انصراف" severity="secondary" text />
                </Link>
                <Button type="submit" label="ذخیره مقاله" icon="pi pi-check" :loading="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
