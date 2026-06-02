<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ province: { type: Object, required: true } });
const form = useForm({
    _method: 'put',
    name: props.province.name ?? '',
    slug: props.province.slug ?? '',
    code: props.province.code ?? '',
    sort_order: props.province.sort_order ?? 0,
    is_active: props.province.is_active ?? true,
});
const save = () => form.post(`/admin/provinces/${props.province.id}`, { preserveScroll: true });
</script>

<template>
    <Head title="ویرایش استان" />
    <AppLayout>
        <TopNavTitle title="ویرایش استان" :breadcrumb="[{ label: 'استان‌ها', to: '/admin/provinces' }, { label: 'ویرایش' }]" />
        <div class="card">
            <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="save">
                <InputText v-model="form.name" placeholder="نام" />
                <InputText v-model="form.slug" placeholder="اسلاگ" />
                <InputText v-model="form.code" placeholder="کد" />
                <InputNumber v-model="form.sort_order" :min="0" />
                <div class="flex items-center gap-2"><ToggleSwitch v-model="form.is_active" /><span>فعال</span></div>
                <div class="md:col-span-2"><Button type="submit" label="ذخیره" :loading="form.processing" /></div>
            </form>
        </div>
    </AppLayout>
</template>

