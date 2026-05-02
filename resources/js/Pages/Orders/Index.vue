<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    paymentStatusOptions: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? null);
const paymentStatus = ref(props.filters.payment_status ?? null);
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const rows = ref(Number(props.filters.rows ?? props.orders.per_page ?? 10));
const timeout = ref();

const statusMap = computed(() => Object.fromEntries(props.statusOptions.map((item) => [item.value, item])));
const paymentMap = computed(() => Object.fromEntries(props.paymentStatusOptions.map((item) => [item.value, item])));
const statusFilters = computed(() => [{ label: 'All statuses', value: null }, ...props.statusOptions]);
const paymentFilters = computed(() => [{ label: 'All payments', value: null }, ...props.paymentStatusOptions]);

const money = (value) => Number(value ?? 0).toLocaleString('fa-IR');

const load = (extra = {}) => {
    router.get('/orders', {
        search: search.value || undefined,
        status: status.value || undefined,
        payment_status: paymentStatus.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        rows: rows.value,
        ...extra
    }, {
        preserveState: true,
        replace: true
    });
};

watch([status, paymentStatus, dateFrom, dateTo], () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const destroyOrder = (order) => {
    confirm.require({
        message: `Delete order ${order.order_number}?`,
        header: 'Delete order',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/orders/${order.id}`, { preserveScroll: true })
    });
};
</script>

<template>
    <Head title="Orders">
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="Orders" :breadcrumb="[{ label: 'Orders' }]">
            <template #pageAction>
                <Link href="/orders/create">
                    <Button label="New Order" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="orders.data"
                dataKey="id"
                lazy
                paginator
                :first="(orders.current_page - 1) * orders.per_page"
                :rows="orders.per_page"
                :totalRecords="orders.total"
                :rowsPerPageOptions="[10, 20, 50]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="Order, customer, mobile" />
                        </IconField>
                        <Select v-model="status" :options="statusFilters" optionLabel="label" optionValue="value" class="w-full" />
                        <Select v-model="paymentStatus" :options="paymentFilters" optionLabel="label" optionValue="value" class="w-full" />
                        <InputText v-model="dateFrom" type="date" class="w-full" />
                        <InputText v-model="dateTo" type="date" class="w-full" />
                    </div>
                </template>

                <template #empty>No orders found.</template>

                <Column field="order_number" header="Order #" style="min-width: 11rem" />
                <Column header="Customer" style="min-width: 12rem">
                    <template #body="{ data }">{{ data.customer?.name || '-' }}</template>
                </Column>
                <Column header="Mobile" style="min-width: 10rem">
                    <template #body="{ data }">{{ data.customer?.phone || '-' }}</template>
                </Column>
                <Column field="status" header="Status" style="width: 9rem">
                    <template #body="{ data }">
                        <Tag :value="statusMap[data.status]?.label ?? data.status" :severity="statusMap[data.status]?.severity ?? 'secondary'" />
                    </template>
                </Column>
                <Column field="payment_status" header="Payment" style="width: 9rem">
                    <template #body="{ data }">
                        <Tag :value="paymentMap[data.payment_status]?.label ?? data.payment_status" :severity="paymentMap[data.payment_status]?.severity ?? 'secondary'" />
                    </template>
                </Column>
                <Column field="total" header="Total" style="min-width: 9rem">
                    <template #body="{ data }">{{ money(data.total) }}</template>
                </Column>
                <Column field="items_count" header="Items" style="width: 6rem" />
                <Column field="created_at" header="Created At" style="min-width: 11rem" />
                <Column header="Actions" style="width: 10rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/orders/${data.id}`">
                                <Button icon="pi pi-eye" rounded text severity="info" aria-label="View" />
                            </Link>
                            <Link :href="`/orders/${data.id}/edit`">
                                <Button icon="pi pi-pencil" rounded text severity="secondary" aria-label="Edit" />
                            </Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="Delete" @click="destroyOrder(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
