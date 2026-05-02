<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';

const props = defineProps({ brands: { type: Array, default: () => [] } });
const confirm = useConfirm();
const visible = ref(false);
const editing = ref(null);
const form = useForm({
    _method: 'post',
    name: '',
    slug: '',
    logo: null,
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
    canonical_url: '',
    seo_index: true,
    seo_follow: true
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form._method = 'post';
    form.seo_index = true;
    form.seo_follow = true;
    form.clearErrors();
    visible.value = true;
};

const openEdit = (brand) => {
    editing.value = brand;
    form._method = 'put';
    form.name = brand.name;
    form.slug = brand.slug;
    form.logo = null;
    form.meta_title = brand.meta_title ?? '';
    form.meta_description = brand.meta_description ?? '';
    form.meta_keywords = brand.meta_keywords ?? '';
    form.canonical_url = brand.canonical_url ?? '';
    form.seo_index = brand.seo_index ?? true;
    form.seo_follow = brand.seo_follow ?? true;
    form.clearErrors();
    visible.value = true;
};

const save = () => {
    const options = { preserveScroll: true, forceFormData: true, onSuccess: () => (visible.value = false) };
    editing.value ? form.post(`/brands/${editing.value.slug}`, options) : form.post('/brands', options);
};

const destroyItem = (brand) => {
    confirm.require({
        message: `برند «${brand.name}» حذف شود؟`,
        header: 'حذف برند',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/brands/${brand.slug}`, { preserveScroll: true })
    });
};
</script>

<template>
    <Head title="برندها" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="مدیریت برندها" :breadcrumb="[{ label: 'برندها' }]">
            <template #pageAction><Button label="برند جدید" icon="pi pi-plus" @click="openCreate" /></template>
        </TopNavTitle>
        <div class="card">
            <DataTable :value="brands" dataKey="id" paginator :rows="10" showGridlines>
                <Column header="لوگو" style="width: 6rem">
                    <template #body="{ data }">
                        <img v-if="data.logo" :src="`/storage/${data.logo}`" class="h-12 w-12 rounded-md object-cover" :alt="data.name" />
                        <i v-else class="pi pi-image text-surface-400" />
                    </template>
                </Column>
                <Column field="name" header="نام" sortable />
                <Column field="slug" header="Slug" />
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
        <Dialog v-model:visible="visible" modal :header="editing ? 'ویرایش برند' : 'ایجاد برند'" :style="{ width: '48rem', maxWidth: '95vw' }">
            <div class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputText v-model="form.name" placeholder="نام" class="w-full" />
                    <FileUpload mode="basic" customUpload auto chooseLabel="انتخاب لوگو" accept="image/*" @select="form.logo = $event.files[0]" />
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
