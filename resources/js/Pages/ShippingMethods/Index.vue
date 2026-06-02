<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

const props = defineProps({
    shippingMethods: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) }
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? null);
const isActive = ref(props.filters.is_active === '' ? '' : (props.filters.is_active ?? ''));
const rows = ref(Number(props.filters.rows ?? props.shippingMethods.per_page ?? 15));
const timeout = ref();

const typeOptions = [
    { label: 'همه نوع‌ها', value: null },
    { label: 'ارسال ثابت', value: 'fixed' },
    { label: 'ارسال رایگان', value: 'free' },
    { label: 'بر اساس وزن', value: 'weight_based' },
    { label: 'بر اساس شهر', value: 'city_based' },
    { label: 'تحویل حضوری', value: 'pickup' }
];

const statusOptions = [
    { label: 'همه وضعیت‌ها', value: '' },
    { label: 'فعال', value: true },
    { label: 'غیرفعال', value: false }
];

const typeLabel = {
    fixed: 'ارسال ثابت',
    free: 'ارسال رایگان',
    weight_based: 'بر اساس وزن',
    city_based: 'بر اساس شهر',
    pickup: 'تحویل حضوری'
};

const load = (extra = {}) => {
    router.get('/admin/shipping-methods', {
        search: search.value || undefined,
        type: type.value || undefined,
        is_active: isActive.value === '' ? undefined : isActive.value,
        rows: rows.value,
        ...extra
    }, { preserveState: true, replace: true });
};

watch([type, isActive], () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 400);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const toggleStatus = (item) => {
    router.patch(`/admin/shipping-methods/${item.id}/toggle-status`, {}, { preserveScroll: true });
};

const destroyItem = (item) => {
    confirm.require({
        message: `روش ارسال «${item.name}» حذف شود؟`,
        header: 'حذف روش ارسال',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/shipping-methods/${item.id}`, { preserveScroll: true })
    });
};

const formatMoney = (value) => (value == null || value === '' ? '-' : Number(value).toLocaleString('fa-IR'));
</script>

<template>
    <Head title="روش‌های ارسال" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="روش‌های ارسال" :breadcrumb="[{ label: 'روش‌های ارسال' }]">
            <template #pageAction>
                <Link href="/admin/shipping-methods/create">
                    <Button label="روش ارسال جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="shippingMethods.data"
                dataKey="id"
                lazy
                paginator
                :first="(shippingMethods.current_page - 1) * shippingMethods.per_page"
                :rows="shippingMethods.per_page"
                :totalRecords="shippingMethods.total"
                :rowsPerPageOptions="[10, 15, 30, 50]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="جستجو در نام، اسلاگ و توضیحات" />
                        </IconField>
                        <Dropdown v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" showClear />
                        <Dropdown v-model="isActive" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </template>

                <template #empty>هیچ روش ارسالی ثبت نشده است</template>

                <Column field="name" header="نام" sortable />
                <Column header="نوع" sortable>
                    <template #body="{ data }">{{ typeLabel[data.type] || data.type }}</template>
                </Column>
                <Column header="هزینه پایه">
                    <template #body="{ data }">{{ formatMoney(data.base_cost) }}</template>
                </Column>
                <Column header="ارسال رایگان از مبلغ">
                    <template #body="{ data }">{{ formatMoney(data.free_from_amount) }}</template>
                </Column>
                <Column field="estimated_delivery_days" header="زمان تحویل" />
                <Column header="وضعیت" style="width: 7rem">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column field="sort_order" header="ترتیب" style="width: 6rem" />
                <Column header="عملیات" style="width: 12rem">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-power-off" rounded text :severity="data.is_active ? 'warning' : 'success'" @click="toggleStatus(data)" />
                            <Link :href="`/admin/shipping-methods/${data.id}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyItem(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
