<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    categoryOptions: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const visible = ref(false);
const editing = ref(null);
const form = useForm({
    name: '',
    slug: '',
    parent_id: null,
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
    canonical_url: '',
    seo_index: true,
    seo_follow: true
});

const resetForm = () => {
    form.name = '';
    form.slug = '';
    form.parent_id = null;
    form.meta_title = '';
    form.meta_description = '';
    form.meta_keywords = '';
    form.canonical_url = '';
    form.seo_index = true;
    form.seo_follow = true;
    form.clearErrors();
};

const openCreate = () => {
    editing.value = null;
    resetForm();
    visible.value = true;
};

const openEdit = (category) => {
    editing.value = category;
    form.name = category.name;
    form.slug = category.slug;
    form.parent_id = category.parent_id;
    form.meta_title = category.meta_title ?? '';
    form.meta_description = category.meta_description ?? '';
    form.meta_keywords = category.meta_keywords ?? '';
    form.canonical_url = category.canonical_url ?? '';
    form.seo_index = category.seo_index ?? true;
    form.seo_follow = category.seo_follow ?? true;
    form.clearErrors();
    visible.value = true;
};

const save = () => {
    const options = { preserveScroll: true, onSuccess: () => (visible.value = false) };
    editing.value ? form.put(`/admin/categories/${editing.value.slug}`, options) : form.post('/admin/categories', options);
};

const destroyItem = (category) => {
    confirm.require({
        message: `دسته‌بندی «${category.name}» حذف شود؟`,
        header: 'حذف دسته‌بندی',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/categories/${category.slug}`, { preserveScroll: true })
    });
};
</script>

<template>
    <Head title="دسته‌بندی‌ها" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="مدیریت دسته‌بندی‌ها" :breadcrumb="[{ label: 'دسته‌بندی‌ها' }]">
            <template #pageAction>
                <Button label="دسته‌بندی جدید" icon="pi pi-plus" @click="openCreate" />
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable :value="categories" dataKey="id" paginator :rows="10" showGridlines>
                <template #empty>دسته‌بندی‌ای پیدا نشد.</template>
                <Column field="id" header="شناسه" sortable style="width: 7rem" />
                <Column field="name" header="نام" sortable />
                <Column field="slug" header="Slug" />
                <Column header="والد">
                    <template #body="{ data }">{{ data.parent?.name ?? '-' }}</template>
                </Column>
                <Column header="SEO">
                    <template #body="{ data }">
                        <Tag :value="data.seo_index ? 'index' : 'noindex'" :severity="data.seo_index ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="عملیات" style="width: 9rem">
                    <template #body="{ data }">
                        <Button icon="pi pi-pencil" rounded text severity="secondary" @click="openEdit(data)" />
                        <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyItem(data)" />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="visible" modal :header="editing ? 'ویرایش دسته‌بندی' : 'ایجاد دسته‌بندی'" :style="{ width: '48rem', maxWidth: '95vw' }">
            <div class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <InputText v-model="form.name" placeholder="نام" class="w-full" :invalid="Boolean(form.errors.name)" />
                        <small v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</small>
                    </div>
                    <Select v-model="form.parent_id" :options="categoryOptions.filter((item) => item.id !== editing?.id)" optionLabel="name" optionValue="id" showClear placeholder="دسته والد" class="w-full" />
                </div>

                <div class="rounded-md border border-surface-200 p-4">
                    <h3 class="mb-3 font-semibold">SEO</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <InputText v-model="form.slug" placeholder="Slug خودکار از نام ساخته می‌شود" class="w-full" />
                            <small class="text-surface-500">اگر خالی باشد خودکار از نام ساخته می‌شود.</small>
                        </div>
                        <InputText v-model="form.canonical_url" placeholder="Canonical URL" class="w-full" />
                        <div>
                            <div class="mb-1 flex justify-between">
                                <span>Meta Title</span>
                                <small :class="form.meta_title.length > 60 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_title.length }}/60</small>
                            </div>
                            <InputText v-model="form.meta_title" class="w-full" />
                        </div>
                        <div>
                            <div class="mb-1 flex justify-between">
                                <span>Meta Description</span>
                                <small :class="form.meta_description.length > 160 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_description.length }}/160</small>
                            </div>
                            <Textarea v-model="form.meta_description" rows="3" class="w-full" />
                        </div>
                        <InputText v-model="form.meta_keywords" placeholder="Meta Keywords" class="w-full md:col-span-2" />
                        <div class="flex items-center gap-3"><ToggleSwitch v-model="form.seo_index" /><span>index</span></div>
                        <div class="flex items-center gap-3"><ToggleSwitch v-model="form.seo_follow" /><span>follow</span></div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="انصراف" severity="secondary" text @click="visible = false" />
                <Button label="ذخیره" :loading="form.processing" @click="save" />
            </template>
        </Dialog>
    </AppLayout>
</template>
