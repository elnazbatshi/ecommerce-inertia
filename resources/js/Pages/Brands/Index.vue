<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import RichTextEditor from '@/Components/CMS/RichTextEditor.vue';
import SeoPreview from '@/Components/CMS/SeoPreview.vue';
import TagInput from '@/Components/TagInput.vue';
import ImageUploader from '@/Components/ImageUploader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';

const props = defineProps({ brands: { type: Array, default: () => [] } });
const confirm = useConfirm();
const visible = ref(false);
const editing = ref(null);
const form = useForm({
    _method: 'post',
    name: '',
    slug: '',
    logo: null,
    remove_logo: false,
    description: '',
    content: '',
    featured_image: null,
    remove_featured_image: false,
    cover_image: null,
    remove_cover_image: false,
    meta_title: '',
    meta_description: '',
    meta_keywords: [],
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
    form.meta_keywords = [];
    form.remove_logo = false;
    form.remove_featured_image = false;
    form.remove_cover_image = false;
    form.clearErrors();
    visible.value = true;
};

const openEdit = (brand) => {
    editing.value = brand;
    form._method = 'put';
    form.name = brand.name;
    form.slug = brand.slug;
    form.logo = null;
    form.remove_logo = false;
    form.description = brand.description ?? '';
    form.content = brand.content ?? '';
    form.featured_image = null;
    form.remove_featured_image = false;
    form.cover_image = null;
    form.remove_cover_image = false;
    form.meta_title = brand.meta_title ?? '';
    form.meta_description = brand.meta_description ?? '';
    form.meta_keywords = Array.isArray(brand.meta_keywords) ? brand.meta_keywords : [];
    form.canonical_url = brand.canonical_url ?? '';
    form.seo_index = brand.seo_index ?? true;
    form.seo_follow = brand.seo_follow ?? true;
    form.clearErrors();
    visible.value = true;
};

const seoUrl = computed(() => `${window.location.origin}/brand/${form.slug || 'نامک-خودکار'}`);

const currentImage = (field, title) => {
    if (!editing.value?.[field] || form[`remove_${field}`] || form[field]) {
        return [];
    }

    return [{ id: `${field}-${editing.value.id}`, url: `/storage/${editing.value[field]}`, name: title }];
};

const removeCurrentImage = (field) => {
    form[field] = null;
    form[`remove_${field}`] = true;
};

const setImage = (field, file) => {
    form[field] = file;
    form[`remove_${field}`] = false;
};

const save = () => {
    const options = { preserveScroll: true, forceFormData: true, onSuccess: () => (visible.value = false) };
    editing.value ? form.post(`/admin/brands/${editing.value.slug}`, options) : form.post('/admin/brands', options);
};

const destroyItem = (brand) => {
    confirm.require({
        message: `برند «${brand.name}» حذف شود؟`,
        header: 'حذف برند',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/brands/${brand.slug}`, { preserveScroll: true })
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
                <Column field="slug" header="نامک" />
                <Column header="سئو">
                    <template #body="{ data }">
                        <Tag :value="data.seo_index ? 'قابل نمایه‌سازی' : 'غیرقابل نمایه‌سازی'" :severity="data.seo_index ? 'success' : 'danger'" />
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
                <InputText v-model="form.name" placeholder="نام" class="w-full" />
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <ImageUploader
                        :modelValue="form.logo"
                        title="لوگوی برند"
                        mode="single"
                        :existingImages="currentImage('logo', 'لوگوی فعلی')"
                        :error="form.errors.logo"
                        @update:modelValue="setImage('logo', $event)"
                        @remove-existing="removeCurrentImage('logo')"
                    />
                    <ImageUploader
                        :modelValue="form.featured_image"
                        title="تصویر شاخص برند"
                        mode="single"
                        :existingImages="currentImage('featured_image', 'تصویر شاخص فعلی')"
                        :error="form.errors.featured_image"
                        @update:modelValue="setImage('featured_image', $event)"
                        @remove-existing="removeCurrentImage('featured_image')"
                    />
                    <div class="md:col-span-2">
                        <ImageUploader
                            :modelValue="form.cover_image"
                            title="تصویر کاور برند"
                            mode="single"
                            :existingImages="currentImage('cover_image', 'تصویر کاور فعلی')"
                            :error="form.errors.cover_image"
                            @update:modelValue="setImage('cover_image', $event)"
                            @remove-existing="removeCurrentImage('cover_image')"
                        />
                    </div>
                </div>
                <div>
                    <label class="mb-2 block font-medium">توضیحات کوتاه</label>
                    <Textarea v-model="form.description" rows="3" class="w-full" />
                </div>
                <div>
                    <label class="mb-2 block font-medium">محتوای برند</label>
                    <RichTextEditor v-model="form.content" />
                </div>
                <div class="rounded-md border border-surface-200 p-4">
                    <h3 class="mb-3 font-semibold">سئو</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <InputText v-model="form.slug" placeholder="نامک خودکار از نام ساخته می‌شود" class="w-full" />
                            <small class="text-surface-500">اگر خالی باشد خودکار از نام ساخته می‌شود.</small>
                        </div>
                        <InputText v-model="form.canonical_url" placeholder="نشانی اصلی" class="w-full" />
                        <div>
                            <div class="mb-1 flex justify-between">
                                <span>عنوان سئو</span>
                                <small :class="form.meta_title.length > 60 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_title.length }}/60</small>
                            </div>
                            <InputText v-model="form.meta_title" class="w-full" />
                        </div>
                        <div>
                            <div class="mb-1 flex justify-between">
                                <span>توضیحات سئو</span>
                                <small :class="form.meta_description.length > 160 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_description.length }}/160</small>
                            </div>
                            <Textarea v-model="form.meta_description" rows="3" class="w-full" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block font-medium">کلمات کلیدی</label>
                            <TagInput v-model="form.meta_keywords" />
                        </div>
                        <div class="flex items-center gap-3"><ToggleSwitch v-model="form.seo_index" /><span>اجازه نمایه‌سازی</span></div>
                        <div class="flex items-center gap-3"><ToggleSwitch v-model="form.seo_follow" /><span>اجازه دنبال‌کردن لینک‌ها</span></div>
                    </div>
                    <div class="mt-4">
                        <SeoPreview :title="form.meta_title" :description="form.meta_description" :url="seoUrl" :fallbackTitle="form.name || 'عنوان برند'" :fallbackDescription="form.description || 'پیش‌نمایش توضیحات برند'" />
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
