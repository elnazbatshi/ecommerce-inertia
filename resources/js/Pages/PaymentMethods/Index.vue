<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

const props = defineProps({
    paymentMethods: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) }
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const driver = ref(props.filters.driver ?? null);
const isActive = ref(props.filters.is_active === '' ? '' : (props.filters.is_active ?? ''));
const rows = ref(Number(props.filters.rows ?? props.paymentMethods.per_page ?? 15));
const timeout = ref();

const driverOptions = [
    { label: 'همه درایورها', value: null },
    { label: 'زرین پال', value: 'zarinpal' },
    { label: 'نکست پی', value: 'nextpay' },
    { label: 'آی دی پی', value: 'idpay' },
    { label: 'کارت به کارت', value: 'card_to_card' },
    { label: 'پرداخت در محل', value: 'cash_on_delivery' },
    { label: 'کیف پول', value: 'wallet' },
    { label: 'دستی', value: 'manual' }
];

const feeTypeLabel = {
    none: 'بدون کارمزد',
    fixed: 'مبلغ ثابت',
    percent: 'درصدی'
};

const statusOptions = [
    { label: 'همه وضعیت‌ها', value: '' },
    { label: 'فعال', value: true },
    { label: 'غیرفعال', value: false }
];

const load = (extra = {}) => {
    router.get('/admin/payment-methods', {
        search: search.value || undefined,
        driver: driver.value || undefined,
        is_active: isActive.value === '' ? undefined : isActive.value,
        rows: rows.value,
        ...extra
    }, { preserveState: true, replace: true });
};

watch([driver, isActive], () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 400);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const toggleStatus = (item) => {
    router.patch(`/admin/payment-methods/${item.id}/toggle-status`, {}, { preserveScroll: true });
};

const destroyItem = (item) => {
    confirm.require({
        message: `روش پرداخت «${item.name}» حذف شود؟`,
        header: 'حذف روش پرداخت',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/payment-methods/${item.id}`, { preserveScroll: true })
    });
};

const formatMoney = (value) => (value == null || value === '' ? '-' : Number(value).toLocaleString('fa-IR'));
</script>

<template>
    <Head title="روش‌های پرداخت" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="روش‌های پرداخت" :breadcrumb="[{ label: 'روش‌های پرداخت' }]">
            <template #pageAction>
                <Link href="/admin/payment-methods/create">
                    <Button label="روش پرداخت جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="paymentMethods.data"
                dataKey="id"
                lazy
                paginator
                :first="(paymentMethods.current_page - 1) * paymentMethods.per_page"
                :rows="paymentMethods.per_page"
                :totalRecords="paymentMethods.total"
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
                        <Dropdown v-model="driver" :options="driverOptions" optionLabel="label" optionValue="value" class="w-full" showClear />
                        <Dropdown v-model="isActive" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </template>

                <template #empty>هیچ روش پرداختی ثبت نشده است</template>

                <Column field="name" header="نام" sortable />
                <Column field="driver" header="درایور" sortable />
                <Column header="نوع کارمزد">
                    <template #body="{ data }">{{ feeTypeLabel[data.fee_type] || data.fee_type }}</template>
                </Column>
                <Column header="مقدار کارمزد">
                    <template #body="{ data }">{{ formatMoney(data.fee_value) }}</template>
                </Column>
                <Column header="حداقل مبلغ">
                    <template #body="{ data }">{{ formatMoney(data.min_amount) }}</template>
                </Column>
                <Column header="حداکثر مبلغ">
                    <template #body="{ data }">{{ formatMoney(data.max_amount) }}</template>
                </Column>
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
                            <Link :href="`/admin/payment-methods/${data.id}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyItem(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
