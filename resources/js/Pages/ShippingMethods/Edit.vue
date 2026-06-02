<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ShippingMethodForm from '@/Pages/ShippingMethods/Partials/ShippingMethodForm.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    shippingMethod: { type: Object, required: true }
});

const form = useForm({
    _method: 'put',
    name: props.shippingMethod.name ?? '',
    slug: props.shippingMethod.slug ?? '',
    description: props.shippingMethod.description ?? '',
    type: props.shippingMethod.type ?? 'fixed',
    base_cost: props.shippingMethod.base_cost ?? 0,
    free_from_amount: props.shippingMethod.free_from_amount ?? null,
    min_order_amount: props.shippingMethod.min_order_amount ?? null,
    max_order_amount: props.shippingMethod.max_order_amount ?? null,
    estimated_delivery_days: props.shippingMethod.estimated_delivery_days ?? '',
    settings: props.shippingMethod.settings ?? {},
    sort_order: props.shippingMethod.sort_order ?? 0,
    is_active: props.shippingMethod.is_active ?? true
});

const save = () => {
    form.post(`/admin/shipping-methods/${props.shippingMethod.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="ویرایش روش ارسال" />
    <AppLayout>
        <TopNavTitle title="ویرایش روش ارسال" :breadcrumb="[{ label: 'روش‌های ارسال', to: '/admin/shipping-methods' }, { label: 'ویرایش' }]" />
        <div class="card">
            <ShippingMethodForm :form="form" submitLabel="ذخیره تغییرات" :processing="form.processing" @submit="save" />
        </div>
    </AppLayout>
</template>
