<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tag: { type: Object, default: null },
});

const isEdit = computed(() => Boolean(props.tag));
const form = useForm({
    _method: isEdit.value ? 'put' : 'post',
    name: props.tag?.name ?? '',
    slug: props.tag?.slug ?? '',
});

const submit = () => {
    const url = isEdit.value ? `/admin/blog-tags/${props.tag.slug}` : '/admin/blog-tags';
    form.post(url);
};
</script>

<template>
    <Head :title="isEdit ? `ویرایش ${tag.name}` : 'ایجاد تگ بلاگ'" />
    <AppLayout>
        <TopNavTitle :title="isEdit ? `ویرایش ${tag.name}` : 'ایجاد تگ بلاگ'" :breadcrumb="[{ label: 'تگ‌های بلاگ', href: '/admin/blog-tags' }, { label: isEdit ? 'ویرایش' : 'ایجاد' }]">
            <template #pageAction>
                <Link href="/admin/blog-tags">
                    <Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">اطلاعات تگ</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block font-medium">نام</label>
                        <InputText v-model="form.name" class="w-full" :invalid="Boolean(form.errors.name)" />
                        <small v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">اسلاگ</label>
                        <InputText v-model="form.slug" class="w-full" />
                        <small v-if="form.errors.slug" class="text-red-600">{{ form.errors.slug }}</small>
                    </div>
                </div>
            </div>

            <Message v-if="Object.keys(form.errors).length" severity="error">لطفاً خطاهای فرم را بررسی کن.</Message>

            <div class="flex justify-end gap-2">
                <Link href="/admin/blog-tags">
                    <Button type="button" label="انصراف" severity="secondary" text />
                </Link>
                <Button type="submit" label="ذخیره تگ" icon="pi pi-check" :loading="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
