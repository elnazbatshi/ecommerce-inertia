<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';

const props = defineProps({ attributes: { type: Array, default: () => [] } });
const confirm = useConfirm();
const visible = ref(false);
const editing = ref(null);
const form = useForm({ name: '', slug: '', type: '', values: [] });

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.values = [{ value: '', slug: '' }];
    form.clearErrors();
    visible.value = true;
};
const openEdit = (attribute) => {
    editing.value = attribute;
    form.name = attribute.name;
    form.slug = attribute.slug;
    form.type = attribute.type;
    form.values = attribute.values?.map((value) => ({ id: value.id, value: value.value, slug: value.slug })) ?? [];
    form.clearErrors();
    visible.value = true;
};
const save = () => {
    const options = { preserveScroll: true, onSuccess: () => (visible.value = false) };
    editing.value ? form.put(`/attributes/${editing.value.id}`, options) : form.post('/attributes', options);
};
const destroyItem = (attribute) => {
    confirm.require({
        message: `ویژگی «${attribute.name}» حذف شود؟`,
        header: 'حذف ویژگی',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/attributes/${attribute.id}`, { preserveScroll: true })
    });
};
const addValue = () => form.values.push({ value: '', slug: '' });
const removeValue = (index) => form.values.splice(index, 1);
</script>

<template>
    <Head title="ویژگی‌ها" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="مدیریت ویژگی‌ها" :breadcrumb="[{ label: 'ویژگی‌ها' }]">
            <template #pageAction><Button label="ویژگی جدید" icon="pi pi-plus" @click="openCreate" /></template>
        </TopNavTitle>
        <div class="card">
            <DataTable :value="attributes" dataKey="id" paginator :rows="10" showGridlines>
                <Column field="name" header="نام" sortable />
                <Column field="type" header="نوع" />
                <Column header="مقادیر">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-2">
                            <Tag v-for="value in data.values" :key="value.id" :value="value.value" severity="info" />
                        </div>
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
        <Dialog v-model:visible="visible" modal :header="editing ? 'ویرایش ویژگی' : 'ایجاد ویژگی'" :style="{ width: '44rem', maxWidth: '95vw' }">
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <InputText v-model="form.name" placeholder="نام ویژگی" class="w-full" />
                    <InputText v-model="form.slug" placeholder="اسلاگ" class="w-full" />
                    <InputText v-model="form.type" placeholder="نوع مثل color یا size" class="w-full" />
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">مقادیر</span>
                        <Button type="button" icon="pi pi-plus" label="افزودن مقدار" severity="secondary" @click="addValue" />
                    </div>
                    <div v-for="(value, index) in form.values" :key="index" class="grid grid-cols-[1fr_1fr_auto] gap-2">
                        <InputText v-model="value.value" placeholder="مقدار" />
                        <InputText v-model="value.slug" placeholder="اسلاگ" />
                        <Button type="button" icon="pi pi-trash" rounded text severity="danger" @click="removeValue(index)" />
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
