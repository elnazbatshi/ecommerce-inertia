<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    category: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
});

const isEdit = computed(() => Boolean(props.category));
const parentOptions = computed(() => props.categories.filter((item) => item.id !== props.category?.id));
const form = useForm({
    _method: isEdit.value ? 'put' : 'post',
    name: props.category?.name ?? '',
    slug: props.category?.slug ?? '',
    description: props.category?.description ?? '',
    parent_id: props.category?.parent_id ?? null,
    is_active: Boolean(props.category?.is_active ?? true),
    sort_order: Number(props.category?.sort_order ?? 0),
});

const submit = () => {
    const url = isEdit.value ? `/admin/blog-categories/${props.category.slug}` : '/admin/blog-categories';
    form.post(url);
};
</script>

<template>
    <Head :title="isEdit ? `ویرایش ${category.name}` : 'ایجاد دسته‌بندی بلاگ'" />
    <AppLayout>
        <TopNavTitle :title="isEdit ? `ویرایش ${category.name}` : 'ایجاد دسته‌بندی بلاگ'" :breadcrumb="[{ label: 'دسته‌بندی‌های بلاگ', href: '/admin/blog-categories' }, { label: isEdit ? 'ویرایش' : 'ایجاد' }]">
            <template #pageAction>
                <Link href="/admin/blog-categories">
                    <Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">اطلاعات دسته‌بندی</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block font-medium">نام</label>
                        <InputText v-model="form.name" class="w-full" :invalid="Boolean(form.errors.name)" />
                        <small v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">اسلاگ</label>
                        <InputText v-model="form.slug" class="w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">توضیحات</label>
                        <Textarea v-model="form.description" rows="4" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">والد دسته‌بندی</label>
                        <Select v-model="form.parent_id" :options="parentOptions" optionLabel="name" optionValue="id" showClear class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">ترتیب نمایش</label>
                        <InputNumber v-model="form.sort_order" class="w-full" inputClass="w-full" :min="0" />
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-surface-200 p-3">
                        <ToggleSwitch v-model="form.is_active" />
                        <span class="font-medium">فعال</span>
                    </div>
                </div>
            </div>

            <Message v-if="Object.keys(form.errors).length" severity="error">لطفاً خطاهای فرم را بررسی کن.</Message>

            <div class="flex justify-end gap-2">
                <Link href="/admin/blog-categories">
                    <Button type="button" label="انصراف" severity="secondary" text />
                </Link>
                <Button type="submit" label="ذخیره دسته‌بندی" icon="pi pi-check" :loading="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
