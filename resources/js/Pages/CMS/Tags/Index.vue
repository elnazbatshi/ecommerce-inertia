<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';

defineProps({ tags: { type: Array, default: () => [] } });

const confirm = useConfirm();
const visible = ref(false);
const editing = ref(null);
const form = useForm({ _method: 'post', name: '', slug: '' });

const openCreate = () => {
    editing.value = null;
    form.reset();
    form._method = 'post';
    visible.value = true;
};

const openEdit = (tag) => {
    editing.value = tag;
    form._method = 'put';
    form.name = tag.name;
    form.slug = tag.slug;
    visible.value = true;
};

const save = () => {
    const url = editing.value ? `/post-tags/${editing.value.slug}` : '/post-tags';
    form.post(url, { preserveScroll: true, onSuccess: () => visible.value = false });
};

const destroyItem = (tag) => confirm.require({
    message: `برچسب «${tag.name}» حذف شود؟`,
    header: 'حذف برچسب',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => router.delete(`/post-tags/${tag.slug}`, { preserveScroll: true })
});
</script>

<template>
    <Head title="برچسب‌های مقالات" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="برچسب‌های مقالات" :breadcrumb="[{ label: 'محتوا' }, { label: 'برچسب‌ها' }]">
            <template #pageAction><Button label="برچسب جدید" icon="pi pi-plus" @click="openCreate" /></template>
        </TopNavTitle>
        <div class="card">
            <DataTable :value="tags" dataKey="id" showGridlines>
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
        <Dialog v-model:visible="visible" modal :header="editing ? 'ویرایش برچسب' : 'ایجاد برچسب'" :style="{ width: '32rem', maxWidth: '95vw' }">
            <div class="space-y-4">
                <InputText v-model="form.name" placeholder="نام" class="w-full" />
                <InputText v-model="form.slug" placeholder="نامک" class="w-full" />
            </div>
            <template #footer>
                <Button label="انصراف" severity="secondary" text @click="visible = false" />
                <Button label="ذخیره" :loading="form.processing" @click="save" />
            </template>
        </Dialog>
    </AppLayout>
</template>
