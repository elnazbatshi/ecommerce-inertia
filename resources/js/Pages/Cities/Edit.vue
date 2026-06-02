<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    city: { type: Object, required: true },
    provinceOptions: { type: Array, default: () => [] },
});

const form = useForm({
    _method: 'put',
    province_id: props.city.province_id ?? null,
    name: props.city.name ?? '',
    slug: props.city.slug ?? '',
    code: props.city.code ?? '',
    sort_order: props.city.sort_order ?? 0,
    is_active: props.city.is_active ?? true,
});
const save = () => form.post(`/admin/cities/${props.city.id}`, { preserveScroll: true });
</script>

<template>
    <Head title="ویرایش شهر" />
    <AppLayout>
        <TopNavTitle title="ویرایش شهر" :breadcrumb="[{ label: 'شهرها', to: '/admin/cities' }, { label: 'ویرایش' }]" />
        <div class="card">
            <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="save">
                <Dropdown v-model="form.province_id" :options="provinceOptions" optionLabel="label" optionValue="id" filter class="w-full" placeholder="استان" />
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

