<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';

defineProps({ categories: { type: Array, default: () => [] } });

const confirm = useConfirm();
const visible = ref(false);
const editing = ref(null);
const form = useForm({ _method: 'post', name: '', slug: '', description: '' });

const openCreate = () => {
    editing.value = null;
    form.reset();
    form._method = 'post';
    visible.value = true;
};

const openEdit = (category) => {
    editing.value = category;
    form._method = 'put';
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description ?? '';
    visible.value = true;
};

const save = () => {
    const url = editing.value ? `/admin/post-categories/${editing.value.slug}` : '/admin/post-categories';
    form.post(url, { preserveScroll: true, onSuccess: () => visible.value = false });
};

const destroyItem = (category) => confirm.require({
    message: `دسته‌بندی «${category.name}» حذف شود؟`,
    header: 'حذف دسته‌بندی',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/post-categories/${category.slug}`, { preserveScroll: true })
});
</script>

<template>
    <Head title="دسته‌بندی‌های مقالات" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="دسته‌بندی‌های مقالات" :breadcrumb="[{ label: 'محتوا' }, { label: 'دسته‌بندی‌ها' }]">
            <template #pageAction><Button label="دسته‌بندی جدید" icon="pi pi-plus" @click="openCreate" /></template>
        </TopNavTitle>
        <div class="card">
            <DataTable :value="categories" dataKey="id" showGridlines>
                <Column field="name" header="نام" />
                <Column field="slug" header="نامک" />
                <Column field="posts_count" header="تعداد مقاله" />
                <Column header="عملیات" style="width: 9rem">
                    <template #body="{ data }">
                        <Button icon="pi pi-pencil" rounded text severity="secondary" @click="openEdit(data)" />
                        <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyItem(data)" />
                    </template>
                </Column>
            </DataTable>
        </div>
        <Dialog v-model:visible="visible" modal :header="editing ? 'ویرایش دسته‌بندی' : 'ایجاد دسته‌بندی'" :style="{ width: '36rem', maxWidth: '95vw' }">
            <div class="space-y-4">
                <InputText v-model="form.name" placeholder="نام" class="w-full" />
                <InputText v-model="form.slug" placeholder="نامک" class="w-full" />
                <Textarea v-model="form.description" rows="3" placeholder="توضیحات" class="w-full" />
            </div>
            <template #footer>
                <Button label="انصراف" severity="secondary" text @click="visible = false" />
                <Button label="ذخیره" :loading="form.processing" @click="save" />
            </template>
        </Dialog>
    </AppLayout>
</template>
